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

        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
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

                \Illuminate\Support\Facades\Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect($wasAdmin ? route('admin.login') : '/');
            }

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
