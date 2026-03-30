<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DeliveryPartnerRegistrationController extends Controller
{
    public function create(Request $request): View
    {
        $user = $request->user();
        $partner = $user->deliveryPartner;

        return view('account.become-delivery-partner', compact('partner'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->deliveryPartner) {
            return redirect()->route('account.become-delivery-partner')
                ->with('info', 'You are already registered as a delivery partner.');
        }

        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'company_name' => 'nullable|string|max:100',
            'vehicle_type' => 'required|in:bike,scooter,van,truck,other',
            'vehicle_number' => 'nullable|string|max:30',
            'license_number' => 'nullable|string|max:50',
        ]);

        $partnerId = 'DP-' . str_pad(DeliveryPartner::max('id') + 1, 4, '0', STR_PAD_LEFT);

        $user->update(['role' => 'delivery_partner']);

        DeliveryPartner::create([
            'user_id' => $user->id,
            'partner_id' => $partnerId,
            'phone' => $validated['phone'],
            'company_name' => $validated['company_name'] ?? null,
            'vehicle_type' => $validated['vehicle_type'],
            'vehicle_number' => $validated['vehicle_number'] ?? null,
            'license_number' => $validated['license_number'] ?? null,
            'is_active' => true,
            'verification_status' => 'pending',
        ]);

        return redirect()->route('account.become-delivery-partner')
            ->with('success', 'Registration successful! Please upload your documents to complete the process.');
    }

    public function uploadDocuments(Request $request): RedirectResponse
    {
        $user = $request->user();
        $partner = $user->deliveryPartner;

        if (!$partner) {
            return redirect()->route('account.become-delivery-partner')
                ->with('error', 'Please complete Step 1 registration first.');
        }

        $validated = $request->validate([
            'id_proof' => ($partner->id_proof ? 'nullable' : 'required') . '|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'license_document' => ($partner->license_document ? 'nullable' : 'required') . '|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'address_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $data = [];

        if ($request->hasFile('id_proof')) {
            if ($partner->id_proof) {
                Storage::disk('public')->delete($partner->id_proof);
            }
            $data['id_proof'] = $request->file('id_proof')->store('delivery-partners/documents', 'public');
        }

        if ($request->hasFile('license_document')) {
            if ($partner->license_document) {
                Storage::disk('public')->delete($partner->license_document);
            }
            $data['license_document'] = $request->file('license_document')->store('delivery-partners/documents', 'public');
        }

        if ($request->hasFile('address_proof')) {
            if ($partner->address_proof) {
                Storage::disk('public')->delete($partner->address_proof);
            }
            $data['address_proof'] = $request->file('address_proof')->store('delivery-partners/documents', 'public');
        }

        if ($partner->verification_status === 'rejected' && !empty($data)) {
            $data['verification_status'] = 'pending';
            $data['verification_note'] = null;
            $data['verified_at'] = null;
        }

        if (!empty($data)) {
            $partner->update($data);
        }

        return redirect()->route('account.become-delivery-partner')
            ->with('success', 'Documents uploaded successfully! Your application is now under review.');
    }
}
