<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminSection
{
    public function handle(Request $request, Closure $next, string $section): Response
    {
        $user = $request->user('admin');

        if (!$user || !$user->canAccessSection($section)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to access this section.'], 403);
            }

            abort(403, 'You do not have permission to access this section.');
        }

        return $next($request);
    }
}
