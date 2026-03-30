<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                // Only redirect if the user actually belongs to this guard's panel
                $redirect = match ($guard) {
                    'admin' => $user->role === 'admin' || $user->role === 'staff' ? route('admin.dashboard') : null,
                    'delivery' => $user->role === 'delivery_partner' ? route('delivery.dashboard') : null,
                    'seller' => $user->role === 'seller' ? route('seller.dashboard') : null,
                    default => '/',
                };

                if ($redirect) {
                    return redirect($redirect);
                }
            }
        }

        return $next($request);
    }
}
