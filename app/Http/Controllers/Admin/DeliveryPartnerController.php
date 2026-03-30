<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DeliveryPartnerController extends Controller
{
    public function index(Request $request): View
    {
        $query = DeliveryPartner::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('partner_id', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($uq) => $uq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('vehicle_type')) {
            $query->where('vehicle_type', $request->vehicle_type);
        }

        $partners = $query->latest()->paginate(10)->withQueryString();

        $stats = [
            'total' => DeliveryPartner::count(),
            'active' => DeliveryPartner::where('is_active', true)->count(),
            'inactive' => DeliveryPartner::where('is_active', false)->count(),
            'on_delivery' => DeliveryPartner::whereHas('orders', fn ($q) => $q->whereIn('status', ['shipped', 'out_for_delivery']))->count(),
        ];

        return view('admin.delivery-partners.index', compact('partners', 'stats'));
    }

    public function create(): View
    {
        return view('admin.delivery-partners.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'company_name' => 'nullable|string|max:100',
            'vehicle_type' => 'required|in:bike,scooter,van,truck,other',
            'vehicle_number' => 'nullable|string|max:30',
            'license_number' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'],
            'role' => 'delivery_partner',
        ]);

        $partnerId = 'DP-' . str_pad(DeliveryPartner::max('id') + 1, 4, '0', STR_PAD_LEFT);

        DeliveryPartner::create([
            'user_id' => $user->id,
            'partner_id' => $partnerId,
            'phone' => $validated['phone'],
            'company_name' => $validated['company_name'] ?? null,
            'vehicle_type' => $validated['vehicle_type'],
            'vehicle_number' => $validated['vehicle_number'] ?? null,
            'license_number' => $validated['license_number'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'verification_status' => 'pending',
        ]);

        return redirect()->route('admin.delivery-partners.index')->with('success', 'Delivery partner created successfully.');
    }

    public function show(DeliveryPartner $deliveryPartner): View
    {
        $deliveryPartner->load('user');

        $orders = $deliveryPartner->orders()
            ->with('user', 'items')
            ->latest()
            ->paginate(10);

        $stats = [
            'active' => $deliveryPartner->orders()->whereIn('status', ['shipped', 'out_for_delivery'])->count(),
            'delivered' => $deliveryPartner->orders()->where('status', 'delivered')->count(),
            'total' => $deliveryPartner->orders()->count(),
        ];

        return view('admin.delivery-partners.show', compact('deliveryPartner', 'orders', 'stats'));
    }

    public function edit(DeliveryPartner $deliveryPartner): View
    {
        $deliveryPartner->load('user');

        return view('admin.delivery-partners.edit', compact('deliveryPartner'));
    }

    public function update(Request $request, DeliveryPartner $deliveryPartner): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email,' . $deliveryPartner->user_id,
            'password' => 'nullable|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'company_name' => 'nullable|string|max:100',
            'vehicle_type' => 'required|in:bike,scooter,van,truck,other',
            'vehicle_number' => 'nullable|string|max:30',
            'license_number' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'verification_status' => 'nullable|in:pending,verified,rejected',
            'verification_note' => 'nullable|string|max:500',
        ]);

        $deliveryPartner->user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);

        if ($request->filled('password')) {
            $deliveryPartner->user->update(['password' => Hash::make($validated['password'])]);
        }

        $partnerData = [
            'phone' => $validated['phone'],
            'company_name' => $validated['company_name'] ?? null,
            'vehicle_type' => $validated['vehicle_type'],
            'vehicle_number' => $validated['vehicle_number'] ?? null,
            'license_number' => $validated['license_number'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ];

        // Handle verification status
        if ($request->filled('verification_status')) {
            $partnerData['verification_status'] = $validated['verification_status'];
            $partnerData['verification_note'] = $validated['verification_note'] ?? null;

            if ($validated['verification_status'] === 'verified' && $deliveryPartner->verification_status !== 'verified') {
                $partnerData['verified_at'] = now();
            }
        }

        $deliveryPartner->update($partnerData);

        return redirect()->route('admin.delivery-partners.index')->with('success', 'Delivery partner updated successfully.');
    }

    public function destroy(DeliveryPartner $deliveryPartner): RedirectResponse
    {
        $deliveryPartner->user->delete();
        $deliveryPartner->delete();

        return redirect()->route('admin.delivery-partners.index')->with('success', 'Delivery partner deleted successfully.');
    }

    public function toggleStatus(DeliveryPartner $deliveryPartner): RedirectResponse
    {
        $deliveryPartner->update(['is_active' => !$deliveryPartner->is_active]);

        $status = $deliveryPartner->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Delivery partner {$status} successfully.");
    }
}
