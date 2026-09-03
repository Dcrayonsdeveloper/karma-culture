<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Every registered route must point at a controller method that exists.
 *
 * Laravel is happy to register a route against a method nobody wrote. Nothing
 * complains until someone hits the URL, and then it is a BadMethodCallException
 * - a 500 with a stack trace where the honest answer is 404.
 *
 * This has bitten the project three times. routes/admin.php already documents
 * two of them in comments (ShippingRateController's index/show, and
 * StoreController's show). A sweep then found thirteen more: Route::resource
 * registers seven methods whether or not the controller has them, and ten admin
 * resources plus two account ones had no show(), account returns had no
 * edit/update/destroy, and /admin/products/{id}/duplicate named a method that
 * was never written.
 *
 * A comment cannot enforce this. This test can.
 */
class RouteIntegrityTest extends TestCase
{
    public function test_every_route_resolves_to_a_real_controller_method(): void
    {
        $broken = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();

            // Closures and framework-supplied actions have nothing to check.
            if (! str_contains($action, '@')) {
                continue;
            }

            [$class, $method] = explode('@', $action, 2);

            if (! class_exists($class)) {
                $broken[] = sprintf('%s -> missing class %s', $route->uri(), $class);
                continue;
            }

            if (! method_exists($class, $method)) {
                $broken[] = sprintf(
                    '%s /%s -> %s::%s() does not exist',
                    implode('|', $route->methods()),
                    $route->uri(),
                    class_basename($class),
                    $method
                );
            }
        }

        $this->assertSame(
            [],
            $broken,
            "These routes would 500 rather than answer:\n  ".implode("\n  ", $broken)
                ."\n\nEither write the method, or narrow the registration with ->only([...]) / ->except([...])."
        );
    }

    public function test_no_two_routes_answer_the_same_get_page_from_different_paths(): void
    {
        // Duplicate paths for one page split it in Google's index and let the
        // canonical tag disagree with the sitemap, which is what /products/{slug}
        // and /categories/{slug} were doing next to /product/ and /category/.
        // A deliberate alias is fine - it just has to be a redirect, not a
        // second route that answers 200.
        // The one sanctioned second path, and why it cannot be a redirect:
        // /wishlist/items is read with a ?ids= list, and Laravel's redirect routes
        // forward path parameters only. A 301 would drop the ids and answer
        // "empty wishlist" rather than sending the caller anywhere useful. It is
        // here for JS bundles that were already in a browser when the path moved,
        // and can go once none can be.
        $allowed = [
            'App\Http\Controllers\WishlistController@items',
        ];

        $seen = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();

            if (! str_contains($action, '@') || ! in_array('GET', $route->methods(), true)) {
                continue;
            }

            // Redirect routes are the sanctioned way to keep an old path alive.
            if (str_contains($action, 'RedirectController')) {
                continue;
            }

            if (in_array($action, $allowed, true)) {
                continue;
            }

            $seen[$action][] = $route->uri();
        }

        $duplicated = array_filter($seen, fn ($uris) => count($uris) > 1);

        $report = [];
        foreach ($duplicated as $action => $uris) {
            $report[] = class_basename($action).': /'.implode(', /', $uris);
        }

        $this->assertSame(
            [],
            $report,
            "These actions answer 200 at more than one path:\n  ".implode("\n  ", $report)
        );
    }
}
