<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsDeliveryPartner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('delivery');

        if (!$user || !$user->isDeliveryPartner()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access Denied'], 403);
            }

            return redirect()->route('delivery.login');
        }

        $partner = $user->deliveryPartner;
        if (!$partner || !$partner->is_active) {
            auth('delivery')->logout();
            return redirect()->route('delivery.login')
                ->withErrors(['email' => 'Your account has been deactivated.']);
        }

        // Allow access to documents page and logout for unverified partners
        $allowedRoutes = ['delivery.documents', 'delivery.documents.upload', 'delivery.logout'];
        if (in_array($request->route()?->getName(), $allowedRoutes)) {
            return $next($request);
        }

        // Redirect unverified partners to document upload page
        if (!$partner->hasDocuments()) {
            return redirect()->route('delivery.documents');
        }

        if (!$partner->isVerified()) {
            return redirect()->route('delivery.documents');
        }

        return $next($request);
    }
}
