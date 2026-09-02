<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Setting;
use App\Models\UserAddress;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
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

        Route::model('address', UserAddress::class);

        // The site-wide password policy. Every caller of ValidationRules::password()
        // - registration, password reset, the profile screens and the API - resolves
        // Password::defaults() at validation time, so this one callback is the whole
        // policy and there is nothing to keep in sync per form.
        //
        // Deliberately expressed as Laravel's composable rules rather than the
        // equivalent regex. A pattern such as
        //   ^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$
        // enforces the same four classes but also *closes the character set*: it
        // rejects '#', '_', '-', '~' and spaces, which throws out stronger
        // passwords than it admits and breaks password managers that generate
        // them. ->symbols() asks for at least one symbol without limiting which.
        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers()->symbols());

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
