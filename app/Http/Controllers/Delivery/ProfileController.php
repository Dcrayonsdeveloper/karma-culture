<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function documents(Request $request): View
    {
        $partner = $request->user('delivery')->deliveryPartner;

        return view('delivery.documents', compact('partner'));
    }

    public function uploadDocuments(Request $request): RedirectResponse
    {
        $partner = $request->user('delivery')->deliveryPartner;

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

        // Reset verification if re-uploading after rejection
        if ($partner->verification_status === 'rejected' && !empty($data)) {
            $data['verification_status'] = 'pending';
            $data['verification_note'] = null;
            $data['verified_at'] = null;
        }

        if (!empty($data)) {
            $partner->update($data);
        }

        $partner->refresh();

        if ($partner->hasDocuments() && $partner->verification_status === 'pending') {
            return back()->with('success', 'Documents uploaded successfully. Please wait for admin verification.');
        }

        return back()->with('success', 'Documents updated successfully.');
    }
}
