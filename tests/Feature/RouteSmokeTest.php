<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Tests\TestCase;

/**
 * Every parameterless GET route, requested in the auth context it was written
 * for. A 500 here is a page that is broken for real visitors; anything below
 * that (200, a redirect, a 403, an empty-state 404) is a decision the route is
 * allowed to make.
 *
 * Parameterised routes are covered by the feature tests for their own areas —
 * they need fixtures this sweep has no way to invent.
 */
class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Routes that cannot be probed by a blind GET: they end the session, stream
     * a file, or hand off to a payment provider.
     */
    private const SKIP = [
        'logout',
        'admin.logout',
        'admin.products.export',
        'admin.customers.export',
        'admin.orders.export',
        'admin.reports.export',
        'admin.newsletter.export',
        'admin.enquiries.export',
        'admin.audit-logs.export',
    ];

    private function routesToProbe(): array
    {
        $out = [];

        foreach (Router::getRoutes() as $route) {
            /** @var Route $route */
            if (!in_array('GET', $route->methods(), true)) continue;
            if (str_contains($route->uri(), '{')) continue;

            $name = $route->getName() ?? '';
            if (in_array($name, self::SKIP, true)) continue;
            if (str_contains($route->uri(), 'export') || str_contains($route->uri(), 'download')) continue;
            if (str_starts_with($route->uri(), '_') || str_starts_with($route->uri(), 'sanctum')) continue;

            $middleware = $route->gatherMiddleware();
            $guard = 'public';
            foreach ($middleware as $m) {
                if (!is_string($m)) continue;
                if ($m === 'auth:admin' || str_contains($m, 'admin.auth') || str_starts_with($route->uri(), 'admin/')) {
                    $guard = 'admin';
                } elseif ($m === 'auth' && $guard === 'public') {
                    $guard = 'customer';
                }
            }

            $out[] = ['uri' => '/' . ltrim($route->uri(), '/'), 'guard' => $guard, 'name' => $name];
        }

        return $out;
    }

    public function test_no_parameterless_get_route_returns_a_server_error(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $adminUser = User::factory()->create(['role' => 'admin']);
        Admin::create([
            'user_id' => $adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $broken = [];

        foreach ($this->routesToProbe() as $route) {
            $request = match ($route['guard']) {
                'admin' => $this->actingAs($adminUser, 'admin'),
                'customer' => $this->actingAs($customer),
                default => $this,
            };

            try {
                $status = $request->get($route['uri'])->getStatusCode();
            } catch (\Throwable $e) {
                $broken[] = sprintf('%s [%s] threw %s: %s',
                    $route['uri'], $route['guard'], class_basename($e), $e->getMessage());
                continue;
            }

            if ($status >= 500) {
                $broken[] = sprintf('%s [%s] returned %d', $route['uri'], $route['guard'], $status);
            }
        }

        $this->assertSame([], $broken, "Routes returning a server error:\n" . implode("\n", $broken));
    }
}
