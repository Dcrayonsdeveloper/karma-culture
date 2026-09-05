<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Setting;
use App\Models\UserAddress;
use App\Rules\ValidationRules;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Apply timezone from settings
        try {
            $timezone = Setting::get('timezone', config('app.timezone'));
            if ($timezone && in_array($timezone, timezone_identifiers_list())) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }
        } catch (\Exception $e) {
            // Settings table may not exist during migrations
        }

        // Apply mail settings from Settings > Email.
        //
        // That whole tab used to be write-only: the form saved mail_host,
        // mail_username and the rest to the settings table and reported
        // success, but nothing anywhere read those keys back, so mail kept
        // going out over whatever was in .env. Same shape as the timezone
        // block above - only override a key the admin has actually filled in,
        // so an empty settings table leaves the .env configuration alone.
        try {
            $mail = Setting::getGroup('email');

            $driver = $mail['mail_driver'] ?? null;
            if ($driver && array_key_exists($driver, config('mail.mailers', []))) {
                config(['mail.default' => $driver]);
            }

            foreach (['mail_host' => 'host', 'mail_port' => 'port', 'mail_username' => 'username', 'mail_password' => 'password'] as $key => $option) {
                if (($mail[$key] ?? '') !== '' && ($mail[$key] ?? null) !== null) {
                    config(["mail.mailers.smtp.{$option}" => $mail[$key]]);
                }
            }

            // Laravel 11 drives the SMTP transport off `scheme`; `encryption`
            // is the legacy spelling the form still uses. Set both so either
            // path resolves to the same transport.
            $encryption = $mail['mail_encryption'] ?? null;
            if ($encryption) {
                config([
                    'mail.mailers.smtp.encryption' => $encryption,
                    'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
                ]);
            }

            if (($mail['mail_from_address'] ?? '') !== '') {
                config(['mail.from.address' => $mail['mail_from_address']]);
            }
            if (($mail['mail_from_name'] ?? '') !== '') {
                config(['mail.from.name' => $mail['mail_from_name']]);
            }
        } catch (\Exception $e) {
            // Settings table may not exist during migrations
        }

        // The verification link, anchored to the configured site address.
        //
        // Laravel builds it with a temporary signed route, which resolves
        // against the *incoming request's* host. This app trusts proxies with
        // `at: '*'` and accepts X-Forwarded-Host (bootstrap/app.php), and
        // registers no trusted-host list, so that host is whatever the caller
        // says it is. Anyone can post this shop's own registration form with
        // somebody else's address and `X-Forwarded-Host: example.invalid`, and
        // that person is emailed a genuine, signed, "verify your email" message
        // from us pointing at the attacker's domain.
        //
        // This is the open item the password-reset fix named and did not close;
        // App\Notifications\ResetPasswordNotification anchors its own link the
        // same way, off config('app.url'), which is set on the server and no
        // header can move.
        //
        // Pinned by generating the URL under a forced root rather than by
        // stitching config('app.url') onto a relative route, because the route
        // is behind `signed` and that middleware validates the *absolute* URL:
        // a signature computed over a relative path would be recomputed over
        // the full one and every verification link would 403. Forcing the root
        // first means the signature covers exactly the address the customer is
        // sent to, which is also what makes a spoofed host fail validation
        // instead of working.
        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            // The scheme has to be pinned as well as the host: forceRootUrl
            // keeps the host but the generator still takes the scheme from the
            // current request, so a link generated off an http request - or
            // from the console, which has none - would be signed as http and
            // then fail validation when the customer arrives over https.
            $root = (string) config('app.url');
            URL::forceRootUrl($root);
            URL::forceScheme(parse_url($root, PHP_URL_SCHEME) ?: 'https');

            try {
                return URL::temporarySignedRoute(
                    'verification.verify',
                    now()->addMinutes((int) config('auth.verification.expire', 60)),
                    [
                        'id' => $notifiable->getKey(),
                        'hash' => sha1($notifiable->getEmailForVerification()),
                    ]
                );
            } finally {
                URL::forceRootUrl(null);
                URL::forceScheme(null);
            }
        });

        Route::model('address', UserAddress::class);

        // The site-wide password policy. Every caller of ValidationRules::password()
        // - registration, password reset, the profile screens and the API - resolves
        // Password::defaults() at validation time, so this one callback is the whole
        // policy and there is nothing to keep in sync per form.
        //
        // Deliberately expressed as Laravel's composable rules rather than the
        // equivalent regex. A pattern such as
        //   ^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{10,}$
        // enforces the same four classes but also *closes the character set*: it
        // rejects '#', '_', '-', '~' and spaces, which throws out stronger
        // passwords than it admits and breaks password managers that generate
        // them. ->symbols() asks for at least one symbol without limiting which.
        //
        // The minimum is ten, raised from Laravel's default of eight. Only the
        // LENGTH moved: mixedCase(), numbers() and symbols() were already the
        // policy, so a password that satisfied the old rule and is ten
        // characters long still satisfies this one. Nothing re-checks a stored
        // hash, so existing accounts keep working and are held to the new
        // minimum only the next time they set a password.
        Password::defaults(fn () => Password::min(ValidationRules::PASSWORD_MIN)->mixedCase()->numbers()->symbols());

        // Named limiters, one bucket each.
        //
        // The guest auth routes shared a single inline `throttle:10,1`, and for
        // a guest the limiter keys on domain|ip WITHOUT the URI — so ten hits
        // across login, register and password-reset (page views included, and a
        // rejected signup costs two) locked the visitor out of all of them.
        // Separate names give separate buckets; the GET pages are no longer
        // throttled at all.
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(10)->by($request->ip()),
            Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
        ]);

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));

        // Asking for a signup verification email. Every hit that gets through
        // can put a real message on the wire, over the same single Gmail app
        // password - and the same daily quota - that order confirmations and
        // password resets go out on.
        //
        // Two clauses because they stop different things. The per-IP clause is
        // a shape for ordinary traffic: one person filling in a signup form
        // corrects a typo once or twice, not eight times a minute. The
        // per-address clause is the one that matters against a script, because
        // bootstrap/app.php trusts every proxy, so request()->ip() is whatever
        // X-Forwarded-For claims and a single machine can present as many as it
        // likes - but it cannot vary the mailbox it is trying to flood. The
        // controller keeps a longer-window bucket on the same address for the
        // daily-quota half of the problem.
        RateLimiter::for('signup-verification', fn (Request $request) => [
            Limit::perMinute(8)->by($request->ip()),
            // is_string first: the limiter runs BEFORE validation, so
            // `email[]=a&email[]=b` reaches it as an array, and casting that to
            // string raises "Array to string conversion" - which this app's
            // error handler turns into a 500 on a request the validator was
            // about to reject with a 422.
            Limit::perMinute(3)->by('signup-verification:'.Str::lower(trim(
                is_string($email = $request->input('email')) ? $email : ''
            ))),
        ]);

        // Reading whether the link has been clicked. No mail, no writes - the
        // open signup form asks about every four seconds while it waits, so
        // this is shaped for a couple of tabs rather than against abuse, and
        // the attempt's uuid is needed to ask at all.
        RateLimiter::for('signup-verification-status', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        // Opening the link. Named for the same reason the two above are, and it
        // is the reason rather than the number that matters here: for a GUEST,
        // an unnamed `throttle:20,1` resolves its bucket to domain|ip with no
        // URI and no route in the key (ThrottleRequests::resolveRequestSignature),
        // so it would share one counter with the newsletter form, the contact
        // form, track-order, guest reviews, ask-a-question, back-in-stock and
        // cart recovery. A customer who had used any of those would find their
        // verification link refused. That is the lockout the comment above the
        // login limiter describes, and this route is the most public one in the
        // flow.
        RateLimiter::for('signup-verification-link', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));

        // The admin bell's ten-second poll - the one route in the panel a
        // browser calls on its own, so the only one where a stuck tab or a
        // shortened interval multiplies into the database with nothing to stop
        // it. One tab asks six times a minute; sixty is a ceiling on a runaway,
        // not a shaper of normal traffic, and leaves room for the handful of
        // admin windows someone might genuinely keep open.
        //
        // Named, not `throttle:60,1`. An unnamed limiter's bucket key is the
        // authenticated user's id and nothing else - no route, no limit, no name
        // (ThrottleRequests::resolveRequestSignature) - so it would share one
        // counter with every other unnamed throttle this person passes through,
        // including the storefront's apply-coupon and checkout limits. A named
        // limiter's key is prefixed with the limiter's own name, which is what
        // gives the poll a bucket of its own.
        //
        // Keyed on the admin, never on the IP: bootstrap/app.php trusts every
        // proxy, so request()->ip() is whatever X-Forwarded-For claims, and one
        // office behind a single NAT would otherwise share a bucket.
        RateLimiter::for(
            'admin-notification-poll',
            fn (Request $request) => Limit::perMinute(60)->by('admin-poll:'.($request->user('admin')?->id ?: $request->ip()))
        );

        Blade::directive('price', function (string $expression) {
            return "<?php echo format_price({$expression}); ?>";
        });

        View::composer('partials.mobile-nav', function ($view) {
            $view->with('navCategories', Category::whereNull('parent_id')
                ->where('is_active', true)
                ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('position')])
                ->orderBy('position')
                ->get());
        });
    }
}
