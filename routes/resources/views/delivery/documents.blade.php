<x-layouts.delivery>
    <x-slot name="title">My Documents</x-slot>

    <div class="max-w-3xl mx-auto">
        {{-- Status Banner --}}
        @if(!$partner->hasDocuments())
            <div class="card mb-6 border-l-4 border-l-warning-500">
                <div class="p-4 flex items-start gap-3">
                    <svg class="w-6 h-6 text-warning-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-warning-700">Documents Required</h3>
                        <p class="text-sm text-warning-600 mt-1">Please upload your ID Proof and Driving License to start accepting deliveries. Your documents will be verified by the admin.</p>
                    </div>
                </div>
            </div>
        @elseif($partner->verification_status === 'pending')
            <div class="card mb-6 border-l-4 border-l-primary-500">
                <div class="p-4 flex items-start gap-3">
                    <svg class="w-6 h-6 text-primary-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-primary-700">Verification Pending</h3>
                        <p class="text-sm text-primary-600 mt-1">Your documents have been uploaded and are pending admin verification. You'll be able to access your dashboard once verified.</p>
                    </div>
                </div>
            </div>
        @elseif($partner->verification_status === 'rejected')
            <div class="card mb-6 border-l-4 border-l-error-500">
                <div class="p-4 flex items-start gap-3">
                    <svg class="w-6 h-6 text-error-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-error-700">Documents Rejected</h3>
                        <p class="text-sm text-error-600 mt-1">Your documents have been rejected. Please re-upload correct documents.</p>
                        @if($partner->verification_note)
                            <div class="mt-2 p-2 bg-error-50 rounded-lg">
                                <p class="text-xs font-medium text-error-700">Reason: {{ $partner->verification_note }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @elseif($partner->verification_status === 'verified')
            <div class="card mb-6 border-l-4 border-l-success-500">
                <div class="p-4 flex items-start gap-3">
                    <svg class="w-6 h-6 text-success-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-success-700">Documents Verified</h3>
                        <p class="text-sm text-success-600 mt-1">Your documents have been verified. You can now accept and deliver orders.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Upload Form --}}
        <form action="{{ route('delivery.documents.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-neutral-900">Upload Documents</h2>
                    <p class="text-xs text-neutral-600">Upload your documents for verification. Accepted formats: JPG, PNG, PDF (max 2MB each)</p>
                </div>
                <div class="p-5 space-y-5">
                    {{-- ID Proof --}}
                    <div>
                        <label for="id_proof" class="form-label">
                            ID Proof (Aadhaar/PAN)
                            @if(!$partner->id_proof) <span class="text-error-500">*</span> @endif
                        </label>
                        @if($partner->id_proof)
                            <div class="flex items-center gap-2 mb-2 p-2.5 bg-success-50 rounded-lg">
                                <svg class="w-4 h-4 text-success-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm text-success-700">Uploaded</span>
                                <a href="{{ asset('storage/' . $partner->id_proof) }}" target="_blank" class="text-xs text-primary-600 hover:text-primary-700 font-medium ml-auto">View</a>
                            </div>
                            <p class="text-xs text-neutral-600 mb-1">Upload a new file to replace the existing one.</p>
                        @endif
                        <input type="file" name="id_proof" id="id_proof" accept="image/*,.pdf" class="form-input w-full" {{ !$partner->id_proof ? 'required' : '' }}>
                        @error('id_proof') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    {{-- Driving License --}}
                    <div>
                        <label for="license_document" class="form-label">
                            Driving License
                            @if(!$partner->license_document) <span class="text-error-500">*</span> @endif
                        </label>
                        @if($partner->license_document)
                            <div class="flex items-center gap-2 mb-2 p-2.5 bg-success-50 rounded-lg">
                                <svg class="w-4 h-4 text-success-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm text-success-700">Uploaded</span>
                                <a href="{{ asset('storage/' . $partner->license_document) }}" target="_blank" class="text-xs text-primary-600 hover:text-primary-700 font-medium ml-auto">View</a>
                            </div>
                            <p class="text-xs text-neutral-600 mb-1">Upload a new file to replace the existing one.</p>
                        @endif
                        <input type="file" name="license_document" id="license_document" accept="image/*,.pdf" class="form-input w-full" {{ !$partner->license_document ? 'required' : '' }}>
                        @error('license_document') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    {{-- Address Proof --}}
                    <div>
                        <label for="address_proof" class="form-label">Address Proof <span class="text-xs text-neutral-600">(Optional)</span></label>
                        @if($partner->address_proof)
                            <div class="flex items-center gap-2 mb-2 p-2.5 bg-success-50 rounded-lg">
                                <svg class="w-4 h-4 text-success-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm text-success-700">Uploaded</span>
                                <a href="{{ asset('storage/' . $partner->address_proof) }}" target="_blank" class="text-xs text-primary-600 hover:text-primary-700 font-medium ml-auto">View</a>
                            </div>
                            <p class="text-xs text-neutral-600 mb-1">Upload a new file to replace the existing one.</p>
                        @endif
                        <input type="file" name="address_proof" id="address_proof" accept="image/*,.pdf" class="form-input w-full">
                        @error('address_proof') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="px-5 pb-5">
                    <button type="submit" class="btn btn-primary w-full justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        {{ $partner->hasDocuments() ? 'Update Documents' : 'Upload Documents' }}
                    </button>
                </div>
            </div>
        </form>

        {{-- Partner Info --}}
        <div class="card mt-6">
            <div class="card-header">
                <h2 class="font-semibold text-neutral-900">Your Details</h2>
            </div>
            <div class="p-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-neutral-600">Partner ID</span>
                    <span class="font-mono font-medium text-neutral-900">{{ $partner->partner_id }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-neutral-600">Name</span>
                    <span class="text-neutral-700">{{ $partner->user->full_name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-neutral-600">Email</span>
                    <span class="text-neutral-700">{{ $partner->user->email }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-neutral-600">Phone</span>
                    <span class="text-neutral-700">{{ $partner->phone ?? '-' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-neutral-600">Vehicle</span>
                    <span class="text-neutral-700">{{ ucfirst($partner->vehicle_type) }}</span>
                </div>
                <div class="flex justify-between text-sm items-center">
                    <span class="text-neutral-600">Verification Status</span>
                    @php
                        $vBadge = match($partner->verification_status) {
                            'verified' => 'badge-success',
                            'rejected' => 'badge-error',
                            default => 'badge-warning',
                        };
                    @endphp
                    <span class="badge {{ $vBadge }}">{{ ucfirst($partner->verification_status) }}</span>
                </div>
            </div>
        </div>
    </div>
</x-layouts.delivery>
