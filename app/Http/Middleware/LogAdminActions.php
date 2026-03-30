<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActions
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log mutating requests (POST, PUT, PATCH, DELETE)
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        // Only log successful responses
        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        $user = $request->user();
        if (!$user) {
            return $response;
        }

        $action = match ($request->method()) {
            'POST' => 'created',
            'PUT', 'PATCH' => 'updated',
            'DELETE' => 'deleted',
            default => 'action',
        };

        $routeName = $request->route()?->getName() ?? $request->path();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'properties' => [
                'description' => ucfirst($action) . ' via ' . $routeName,
                'route' => $routeName,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $response;
    }
}
