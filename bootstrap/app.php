<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The site runs behind Hostinger's proxy. Without this, request()->ip()
        // is the proxy's address for every visitor — so per-IP rate limiters
        // put the whole world in one bucket — and the scheme is read as http,
        // which breaks secure-cookie and absolute-URL generation.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // ContentSecurityPolicy is deliberately NOT registered yet.
        //
        // The policy as written does not match what the storefront loads: it
        // omits fonts.googleapis.com / fonts.gstatic.com (typography), the CDNs
        // that serve CKEditor and icon fonts, and the analytics endpoints, and
        // its script-src cannot accommodate Alpine's standard build or the ~40
        // Blade views with inline <script> blocks. Enabling it as-is blanks out
        // navigation, modals, carousels and the cart.
        //
        // Enable it only after auditing every external origin, starting in
        // Content-Security-Policy-Report-Only against the live site. The
        // stored-XSS vectors it was meant to backstop are already closed at
        // source by safe_html() and the upload mime pinning.

        $middleware->validateCsrfTokens(except: [
            'payu/success',
            'payu/failure',
            'webhooks/shiprocket',
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Where a signed-out visitor is sent depends on which half of the site
        // they were reaching for — and under the api prefix the honest answer is
        // "nowhere". A JSON client asked for data, not for a login form: handing
        // it a 302 to /login means its error handling sees a 200 full of markup
        // and reports either nothing at all or a parse failure, when what it
        // needed was a 401 it could act on. Returning null leaves the
        // AuthenticationException with no redirect attached so the exception
        // handler answers with the status instead.
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->is('api')) {
                return null;
            }
            if ($request->is('admin/*') || $request->is('admin')) {
                return route('admin.login');
            }
            return route('login');
        });

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'admin.section' => \App\Http\Middleware\CheckAdminSection::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'admin.audit' => \App\Http\Middleware\LogAdminActions::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Whether a failure is answered in JSON at all, and which sentence that
        // JSON carries, have to be decided by the SAME test — so the test is
        // written once, here, and both callbacks below close over it.
        //
        // They were two different tests until now, and the gap between them was
        // a leak. shouldRenderJsonWhen() below already says yes to any request
        // anywhere on the site that asked for JSON, but the callback that
        // chooses the wording only answered under the api prefix; everything
        // else fell through to the framework's own convertExceptionToArray(),
        // which publishes an HttpException's raw message even with APP_DEBUG
        // off. The storefront's own fetch() calls live in exactly that gap:
        // PUT and DELETE /cart/{cartItem} send `Accept: application/json` and
        // bind their model implicitly, so a cart row that is no longer there —
        // routine here, because carts are recycled rather than deleted and a
        // second tab can remove the line — answered the shopper's toast with
        // "No query results for model [App\Models\CartItem] 41", and the
        // wishlist routes did the same with the Product class. Keeping the two
        // decisions on one predicate is what stops them drifting apart again.
        $wantsJson = static function (\Illuminate\Http\Request $request): bool {
            return $request->is('api/*') || $request->is('api') || $request->expectsJson();
        };

        // Under the api prefix the answer is JSON whether or not the caller
        // thought to ask for it.
        //
        // Laravel decides HTML-or-JSON from the Accept header alone, so a client
        // that did not send `Accept: application/json` — a curl call, a mobile
        // build with a missing default header, a partner posting to a webhook —
        // was answered with a 302 to the HTML login page instead of a 401, and
        // with a full error page instead of a 404, a 422 or a 500. The status
        // line said 200 and the body was markup, so the caller's error handling
        // never fired: the request looked like it had succeeded. The path is the
        // reliable statement of intent here, because everything mounted under the
        // api prefix is a data endpoint and none of it has an HTML view to fall
        // back to. Outside it, expectsJson() still decides exactly as before.
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, \Throwable $e) use ($wantsJson) {
            return $wantsJson($request);
        });

        // One shape, and one vocabulary, for every failure answered in JSON —
        // the api prefix and the storefront's own fetch() calls alike.
        //
        // Left to itself the framework answers with the exception's own message,
        // and for anything it classes as an HttpException it does so even with
        // APP_DEBUG=false. A missed route-model binding is exactly that case:
        // prepareException() rewraps ModelNotFoundException as a
        // NotFoundHttpException carrying "No query results for model
        // [App\Models\Product] 41", so an ordinary 404 published the internal
        // class name and the key it had been looked up by. Every other status was
        // free to leak whatever text happened to be on the exception in the same
        // way, and a 500 leaks the worst of it — a driver error, a file path, a
        // fragment of a query.
        //
        // So the sentence is chosen HERE, from the status, and the exception's
        // own words are used only where somebody deliberately wrote them: a 4xx
        // raised with no previous exception is an abort() in this codebase
        // choosing what to say ("This item is not in your cart."), while a 4xx
        // that wraps another exception was manufactured by the framework out of
        // an internal failure and its text is diagnostics, not copy. Above 500
        // nothing is passed through at all.
        //
        // The wording is the same wording as KK_API_MESSAGES in
        // resources/js/app.js, so a browser that falls back to the body and a
        // mobile client that reads it directly tell the customer the same thing.
        //
        // Registered BEFORE the 419 callback below, deliberately: render
        // callbacks are consulted in registration order, and that one is written
        // for a Blade form — it answers with a redirect carrying flashed input,
        // which is the wrong answer to anything reading a JSON body. The single
        // exception is carved out at the top of this callback, where a stale
        // token on a sign-out is handed back down to it.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) use ($wantsJson) {
            // A request that is going to be answered with a rendered error page
            // is none of this callback's business: the Blade error views and the
            // redirect-back-with-errors path that every form on the site depends
            // on are left exactly as they were. The test is the same one that
            // decided the response would be JSON in the first place, so there is
            // no request whose body is JSON but whose wording came from
            // somewhere else.
            if (! $wantsJson($request)) {
                return null;
            }

            // The one failure here that is not answered by choosing a sentence:
            // a sign-out whose CSRF token died along with the session it was
            // meant to end. The callback further down does real work for that
            // case — it logs both guards out, cycles the recaller cookie and
            // invalidates the session — and a tidy 419 body reading "refresh and
            // try again" would leave the visitor still signed in, pressing a
            // button that can never succeed. Ownership of that one case stays
            // where the work is.
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                && $e->getStatusCode() === 419
                && $e->getPrevious() instanceof \Illuminate\Session\TokenMismatchException
                && ($request->routeIs('logout', 'admin.logout') || $request->is('logout', 'admin/logout'))) {
                return null;
            }

            // An exception that already carries a finished response has had its
            // body decided by whoever threw it. Rewriting that would throw away a
            // deliberate answer, so it is left to the handler to unwrap.
            if ($e instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
                return null;
            }

            $status = match (true) {
                $e instanceof \Illuminate\Validation\ValidationException => $e->status,
                $e instanceof \Illuminate\Auth\AuthenticationException => 401,
                // Named BEFORE the HttpException case, and before the 500 fallback
                // it would otherwise reach. This callback runs ahead of the
                // handler's own prepareException(), so a findOrFail() miss is
                // still a ModelNotFoundException here, not the NotFoundHttpException
                // it eventually becomes - and without this line it fell to
                // `default => 500`, so a row that simply is not there was reported
                // to the customer as the site having broken.
                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => 404,
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface => $e->getStatusCode(),
                default => 500,
            };

            $canned = [
                400 => 'That request could not be processed. Please check what you entered and try again.',
                401 => 'Please sign in to continue.',
                403 => 'You do not have permission to do that.',
                404 => 'We could not find what you were looking for.',
                405 => 'That address does not accept this kind of request.',
                409 => 'That has already been done. Please refresh the page and try again.',
                419 => 'This page has been open a while and your session expired. Please refresh and try again.',
                422 => 'Please check the highlighted fields and try again.',
                429 => 'That is a few too many attempts. Please wait a moment and try again.',
                500 => 'Something went wrong at our end. Please try again in a moment.',
                502 => 'The service is briefly unavailable. Please try again in a moment.',
                503 => 'The service is briefly unavailable. Please try again in a moment.',
                504 => 'The server took too long to answer. Please try again in a moment.',
            ];

            $message = $canned[$status] ?? ($status >= 500 ? $canned[500] : $canned[400]);

            // 401, 405, 419 and 429 are raised by middleware and by the router
            // before any controller runs, so their text — "Unauthenticated.",
            // "CSRF token mismatch.", "Too Many Attempts.", the list of verbs a
            // route happens to allow — is Laravel talking to a developer. Nothing
            // in app/ or routes/ writes a body of its own for those four, so the
            // canned wording throws away no deliberate sentence; it is also the
            // same judgement KK_FRAMEWORK_STATUSES already makes on the client.
            $frameworkAuthored = in_array($status, [401, 405, 419, 429], true);

            // 404 is not on that list because one controller does write a
            // deliberate one - ProductController's "This product is not
            // available." - and genericising it would lose a better sentence
            // than the canned line. What has to go is the framework's OWN 404
            // text, which is the majority of them and names internals: the
            // router raises "The route api/v1/nope could not be found." and
            // Eloquent raises "No query results for model [App\Models\Product]
            // 12.", publishing a class name and a primary key to whoever asked.
            // Matched on the two shapes rather than on the status, so a written
            // sentence still gets through and a diagnostic never does.
            foreach (['The route ', 'No query results for model'] as $diagnostic) {
                if (str_starts_with(trim($e->getMessage()), $diagnostic)) {
                    $frameworkAuthored = true;
                    break;
                }
            }

            if ($status < 500
                && ! $frameworkAuthored
                && $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                && $e->getPrevious() === null
                && trim($e->getMessage()) !== '') {
                $message = $e->getMessage();
            }

            // A validation summary is the one message the storefront keeps, and
            // only off the api prefix.
            //
            // It reads like framework text but it is not: Laravel builds it from
            // the first line of our own error bag, so it is "Please enter your
            // name." — copy written in a validation rule in this repo, publishing
            // no class, path or query. The web half's JSON callers were written
            // against that contract long before this callback existed and several
            // read `message` as the sentence to show, so genericising it here
            // would quietly downgrade a specific complaint into a shrug on
            // endpoints that have nowhere to put a field note. Under the api
            // prefix it stays generic, deliberately: that envelope's clients read
            // `errors` and write beside the input, and a summary repeating the
            // field's own words is the same sentence printed twice.
            if ($e instanceof \Illuminate\Validation\ValidationException
                && ! $request->is('api/*')
                && ! $request->is('api')
                && trim($e->getMessage()) !== '') {
                $message = $e->getMessage();
            }

            $body = [
                'success' => false,
                'message' => $message,
            ];

            // `errors` is the {field: [messages]} map every other endpoint in
            // this API answers a 422 with, and it is left out rather than sent
            // empty for the same reason those endpoints leave it out: a client
            // tests for the key to decide whether it has anything to write beside
            // an input. Under the api prefix the summary line above stays generic
            // so the banner and the field note never say the same sentence twice.
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $body['errors'] = $e->errors();
            }

            // The diagnosis is not lost, it just stops being published: the
            // exception is still reported to the log either way, and locally —
            // never on production, where app.debug is false — it rides along in
            // its own key so an endpoint can still be debugged from the client.
            if (config('app.debug')) {
                $body['debug'] = [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                    'at' => $e->getFile().':'.$e->getLine(),
                ];
            }

            // The headers are carried over because on some statuses they are the
            // answer: a 429 without Retry-After tells a client to back off
            // without saying for how long, and a 401 without WWW-Authenticate
            // drops the challenge that names the scheme.
            return response()->json(
                $body,
                $status,
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getHeaders() : []
            );
        });

        // A form left open past the session lifetime submits a stale CSRF
        // token. The default response is a dead-end 419 error page; sending
        // the visitor back to the form with a fresh token lets them just
        // retry. Passwords are excluded from the re-fill for safety.
        // Typed on HttpException rather than TokenMismatchException: the
        // handler converts the latter into a 419 HttpException in
        // prepareException(), which runs before render callbacks are
        // consulted, so a callback typed on the original never matches.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419 || ! $e->getPrevious() instanceof \Illuminate\Session\TokenMismatchException) {
                return null;
            }

            // Signing out is the one case where a stale token is not a problem
            // worth reporting: the session it belonged to is what the visitor
            // is asking us to throw away. Finish the job and send them home
            // rather than making them fix a token to be allowed to leave.
            if ($request->routeIs('logout', 'admin.logout') || $request->is('logout', 'admin/logout')) {
                $wasAdmin = $request->routeIs('admin.logout') || $request->is('admin/logout');

                // Log out per guard, not via the facade's default. `Auth::logout()`
                // only touches the `web` guard, so an expired-token admin logout left
                // the `remember_admin_*` recaller cookie on the browser and the DB
                // remember_token uncycled — invalidating the session does not remove
                // either, so the very next request signed the admin straight back in.
                foreach (['web', 'admin'] as $guard) {
                    if (\Illuminate\Support\Facades\Auth::guard($guard)->check()) {
                        \Illuminate\Support\Facades\Auth::guard($guard)->logout();
                    }
                }

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect($wasAdmin ? route('admin.login') : '/');
            }

            // A backstop, not the live path. Every JSON-answered 419 is now
            // claimed by the callback above, which says the same thing in the
            // wording KK_API_MESSAGES uses; this branch only matters again if
            // that callback is ever narrowed or reordered, and it is cheaper to
            // leave standing than to rediscover the day a JSON client starts
            // reading a redirect to the login page as a successful submission.
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session expired. Please refresh the page and try again.',
                ], 419);
            }

            $isAdmin = $request->is('admin', 'admin/*');

            return redirect()->back(fallback: $isAdmin ? route('admin.login') : route('login'))
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->with('error', 'Your session expired. Please try again.');
        });
    })->create();
