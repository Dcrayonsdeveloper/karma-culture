<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $seller = $request->user()->seller;

        return view('seller.settings.index', compact('seller'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $seller = $request->user()->seller;

        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'store_description' => 'nullable|string|max:2000',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $seller->update($validated);

        return back()->with('success', 'Store profile updated successfully.');
    }

    public function updatePayout(Request $request): RedirectResponse
    {
        $seller = $request->user()->seller;

        $validated = $request->validate([
            'payout_method' => 'required|in:bank_transfer,paypal',
            'payout_email' => 'required_if:payout_method,paypal|nullable|email',
            'bank_name' => 'required_if:payout_method,bank_transfer|nullable|string|max:255',
            'bank_account' => 'required_if:payout_method,bank_transfer|nullable|string|max:50',
            'bank_routing' => 'required_if:payout_method,bank_transfer|nullable|string|max:50',
        ]);

        $seller->update($validated);

        return back()->with('success', 'Payout settings updated successfully.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $seller = $request->user()->seller;

        $seller->update([
            'email_notifications' => $request->boolean('email_notifications'),
            'order_notifications' => $request->boolean('order_notifications'),
            'review_notifications' => $request->boolean('review_notifications'),
        ]);

        return back()->with('success', 'Notification settings updated successfully.');
    }
}
