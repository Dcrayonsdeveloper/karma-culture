<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('admin');

        if (! $user || (! $user->isAdmin() && ! $user->isStaff())) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access Denied'], 403);
            }

            // If a non-admin is logged into the admin guard, log them out and
            // send them to the login page instead of a dead-end 403.
            if ($user) {
                Auth::guard('admin')->logout();
            }

            return redirect()->route('admin.login')->with('error', 'Please sign in with an admin account.');
        }

        return $next($request);
    }
}
