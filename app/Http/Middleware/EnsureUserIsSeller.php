<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->seller || $user->seller->status !== 'active') {
            abort(403, 'You must be an approved seller to access this area.');
        }

        return $next($request);
    }
}
