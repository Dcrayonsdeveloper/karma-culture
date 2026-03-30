<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('admin');

        if (!$user || (!$user->isAdmin() && !$user->isStaff())) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access Denied'], 403);
            }

            abort(403, 'Access Denied');
        }

        return $next($request);
    }
}
