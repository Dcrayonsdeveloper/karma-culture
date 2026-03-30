<x-layouts.app>
    <x-slot name="title">Become a Delivery Partner - {{ config('app.name') }}</x-slot>

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'My Account', 'url' => route('account.dashboard')], ['label' => 'Become a Delivery Partner', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            @include('account.partials.sidebar')

            <div class="flex-1" x-data="{
                currentStep: {{ $partner ? ($partner->hasDocuments() ? 3 : 2) : 1 }}
            }">
                {{-- Header --}}
                <div class="rounded-xl p-5 sm:p-6 mb-6 bg-gradient-to-r from-[#6F9CA2]/50 to-[#6F9CA2] text-white">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg sm:text-xl font-bold">Become a Delivery Partner</h1>
                            <p class="text-sm text-white/80 mt-1">Join our delivery network and start earning. Complete the steps below to get started.</p>
                        </div>
                    </div>
                </div>

                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="mb-5 p-3.5 bg-success-50 border border-success-200 rounded-lg text-sm text-success-700 flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-5 p-3.5 bg-error-50 border border-error-200 rounded-lg text-sm text-error-700 flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Step Indicator --}}
                <div class="flex items-center gap-0 mb-6">
                    {{-- Step 1 --}}
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors"
                             :class="currentStep >= 1 ? (currentStep > 1 ? 'bg-success-500 text-white' : 'bg-[#F8931D] text-white') : 'bg-neutral-200 text-neutral-600'">
                            <template x-if="currentStep > 1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="currentStep <= 1">
                                <span>1</span>
                            </template>
                        </div>
                        <span class="text-sm font-medium hidden sm:inline" :class="currentStep >= 1 ? 'text-neutral-900' : 'text-neutral-600'">Details</span>
                    </div>

                    <div class="flex-1 h-0.5 mx-2 sm:mx-3 rounded" :class="currentStep > 1 ? 'bg-success-500' : 'bg-neutral-200'"></div>

                    {{-- Step 2 --}}
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors"
                             :class="currentStep >= 2 ? (currentStep > 2 ? 'bg-success-500 text-white' : 'bg-[#F8931D] text-white') : 'bg-neutral-200 text-neutral-600'">
                            <template x-if="currentStep > 2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="currentStep <= 2">
                                <span>2</span>
                            </template>
                        </div>
                        <span class="text-sm font-medium hidden sm:inline" :class="currentStep >= 2 ? 'text-neutral-900' : 'text-neutral-600'">Documents</span>
                    </div>

                    <div class="flex-1 h-0.5 mx-2 sm:mx-3 rounded" :class="currentStep > 2 ? 'bg-success-500' : 'bg-neutral-200'"></div>

                    {{-- Step 3 --}}
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors"
                             :class="currentStep >= 3 ? 'bg-[#F8931D] text-white' : 'bg-neutral-200 text-neutral-600'">
                            <span>3</span>
                        </div>
                        <span class="text-sm font-medium hidden sm:inline" :class="currentStep >= 3 ? 'text-neutral-900' : 'text-neutral-600'">Verification</span>
                    </div>
                </div>

                {{-- ============ STEP 1: Vehicle & Contact Details ============ --}}
                <div x-show="currentStep === 1" x-cloak>
                    @if(!$partner)
                        <form action="{{ route('account.become-delivery-partner.store') }}" method="POST">
                            @csrf
                            <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                                <div class="px-5 py-4 border-b border-neutral-100">
                                    <h2 class="font-semibold text-neutral-900">Vehicle & Contact Details</h2>
                                    <p class="text-xs text-neutral-600 mt-0.5">Your existing account info (name, email) will be used automatically</p>
                                </div>

                                <div class="p-5 space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="phone" class="block text-sm font-medium text-neutral-700 mb-1.5">Phone Number <span class="text-error-500">*</span></label>
                                            <input type="text" name="phone" id="phone" value="{{ old('phone', auth()->user()->phone) }}" required
                                                   class="w-full px-3 py-2.5 bg-neutral-50 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:border-[#6F9CA2] focus:ring-0 transition-colors"
                                                   placeholder="e.g. +91 9876543210">
                                            @error('phone') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="vehicle_type" class="block text-sm font-medium text-neutral-700 mb-1.5">Vehicle Type <span class="text-error-500">*</span></label>
                                            <select name="vehicle_type" id="vehicle_type" required
                                                    class="w-full px-3 py-2.5 bg-neutral-50 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:border-[#6F9CA2] focus:ring-0 transition-colors">
                                                <option value="">Select vehicle type</option>
                                                <option value="bike" @selected(old('vehicle_type') === 'bike')>Bike</option>
                                                <option value="scooter" @selected(old('vehicle_type') === 'scooter')>Scooter</option>
                                                <option value="van" @selected(old('vehicle_type') === 'van')>Van</option>
                                                <option value="truck" @selected(old('vehicle_type') === 'truck')>Truck</option>
                                                <option value="other" @selected(old('vehicle_type') === 'other')>Other</option>
                                            </select>
                                            @error('vehicle_type') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="vehicle_number" class="block text-sm font-medium text-neutral-700 mb-1.5">Vehicle Number</label>
                                            <input type="text" name="vehicle_number" id="vehicle_number" value="{{ old('vehicle_number') }}"
                                                   class="w-full px-3 py-2.5 bg-neutral-50 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:border-[#6F9CA2] focus:ring-0 transition-colors"
                                                   placeholder="e.g. MH 02 AB 1234">
                                            @error('vehicle_number') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="license_number" class="block text-sm font-medium text-neutral-700 mb-1.5">License Number</label>
                                            <input type="text" name="license_number" id="license_number" value="{{ old('license_number') }}"
                                                   class="w-full px-3 py-2.5 bg-neutral-50 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:border-[#6F9CA2] focus:ring-0 transition-colors"
                                                   placeholder="e.g. MH-0220170012345">
                                            @error('license_number') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <div>
                                        <label for="company_name" class="block text-sm font-medium text-neutral-700 mb-1.5">Company Name <span class="text-xs text-neutral-600">(Optional)</span></label>
                                        <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}"
                                               class="w-full px-3 py-2.5 bg-neutral-50 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:border-[#6F9CA2] focus:ring-0 transition-colors"
                                               placeholder="If you represent a delivery company">
                                        @error('company_name') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="px-5 py-4 bg-neutral-50 border-t border-neutral-100 flex items-center justify-end">
                                    <button type="submit" class="px-6 py-2.5 bg-[#F8931D] hover:bg-[#E07E0A] text-white font-semibold rounded-lg text-sm transition-colors inline-flex items-center gap-2">
                                        Next: Upload Documents
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        {{-- Already completed step 1 - show summary --}}
                        <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                            <div class="px-5 py-4 border-b border-neutral-100 flex items-center justify-between">
                                <h2 class="font-semibold text-neutral-900">Vehicle & Contact Details</h2>
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-success-600 bg-success-50 px-2 py-1 rounded-full">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Completed
                                </span>
                            </div>
                            <div class="p-5">
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span class="text-neutral-600 text-xs">Partner ID</span>
                                        <p class="font-medium text-neutral-900">{{ $partner->partner_id }}</p>
                                    </div>
                                    <div>
                                        <span class="text-neutral-600 text-xs">Phone</span>
                                        <p class="font-medium text-neutral-900">{{ $partner->phone }}</p>
                                    </div>
                                    <div>
                                        <span class="text-neutral-600 text-xs">Vehicle Type</span>
                                        <p class="font-medium text-neutral-900 capitalize">{{ $partner->vehicle_type }}</p>
                                    </div>
                                    @if($partner->vehicle_number)
                                        <div>
                                            <span class="text-neutral-600 text-xs">Vehicle Number</span>
                                            <p class="font-medium text-neutral-900">{{ $partner->vehicle_number }}</p>
                                        </div>
                                    @endif
                                    @if($partner->license_number)
                                        <div>
                                            <span class="text-neutral-600 text-xs">License Number</span>
                                            <p class="font-medium text-neutral-900">{{ $partner->license_number }}</p>
                                        </div>
                                    @endif
                                    @if($partner->company_name)
                                        <div>
                                            <span class="text-neutral-600 text-xs">Company</span>
                                            <p class="font-medium text-neutral-900">{{ $partner->company_name }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ============ STEP 2: Document Upload ============ --}}
                <div x-show="currentStep === 2" x-cloak>
                    @if($partner)
                        <form action="{{ route('account.become-delivery-partner.documents') }}" method="POST" enctype="multipart/form-data"
                              x-data="{
                                  idProofName: '',
                                  licenseName: '',
                                  addressProofName: ''
                              }">
                            @csrf
                            <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                                <div class="px-5 py-4 border-b border-neutral-100">
                                    <h2 class="font-semibold text-neutral-900">Upload Documents</h2>
                                    <p class="text-xs text-neutral-600 mt-0.5">Upload your identity and vehicle documents for verification. Accepted formats: JPG, PNG, PDF (max 2MB each)</p>
                                </div>

                                <div class="p-5 space-y-5">
                                    {{-- ID Proof --}}
                                    <div>
                                        <label class="block text-sm font-medium text-neutral-700 mb-1.5">
                                            ID Proof (Aadhaar / PAN / Voter ID) <span class="text-error-500">*</span>
                                        </label>
                                        @if($partner->id_proof)
                                            <div class="flex items-center gap-2 mb-2 p-2.5 bg-success-50 border border-success-200 rounded-lg text-sm">
                                                <svg class="w-4 h-4 text-success-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span class="text-success-700">Already uploaded</span>
                                                <span class="text-xs text-neutral-600 ml-auto">Upload again to replace</span>
                                            </div>
                                        @endif
                                        <label class="flex items-center justify-center gap-2 w-full px-4 py-3 border-2 border-dashed border-neutral-300 rounded-lg cursor-pointer hover:border-[#6F9CA2] hover:bg-[#6F9CA2]/5/50 transition-colors">
                                            <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span class="text-sm text-neutral-600" x-text="idProofName || 'Choose file...'"></span>
                                            <input type="file" name="id_proof" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                                                   @change="idProofName = $event.target.files[0]?.name || ''"
                                                   {{ !$partner->id_proof ? 'required' : '' }}>
                                        </label>
                                        @error('id_proof') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                                    </div>

                                    {{-- License Document --}}
                                    <div>
                                        <label class="block text-sm font-medium text-neutral-700 mb-1.5">
                                            Driving License <span class="text-error-500">*</span>
                                        </label>
                                        @if($partner->license_document)
                                            <div class="flex items-center gap-2 mb-2 p-2.5 bg-success-50 border border-success-200 rounded-lg text-sm">
                                                <svg class="w-4 h-4 text-success-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span class="text-success-700">Already uploaded</span>
                                                <span class="text-xs text-neutral-600 ml-auto">Upload again to replace</span>
                                            </div>
                                        @endif
                                        <label class="flex items-center justify-center gap-2 w-full px-4 py-3 border-2 border-dashed border-neutral-300 rounded-lg cursor-pointer hover:border-[#6F9CA2] hover:bg-[#6F9CA2]/5/50 transition-colors">
                                            <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span class="text-sm text-neutral-600" x-text="licenseName || 'Choose file...'"></span>
                                            <input type="file" name="license_document" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                                                   @change="licenseName = $event.target.files[0]?.name || ''"
                                                   {{ !$partner->license_document ? 'required' : '' }}>
                                        </label>
                                        @error('license_document') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                                    </div>

                                    {{-- Address Proof --}}
                                    <div>
                                        <label class="block text-sm font-medium text-neutral-700 mb-1.5">
                                            Address Proof <span class="text-xs text-neutral-600">(Optional)</span>
                                        </label>
                                        @if($partner->address_proof)
                                            <div class="flex items-center gap-2 mb-2 p-2.5 bg-success-50 border border-success-200 rounded-lg text-sm">
                                                <svg class="w-4 h-4 text-success-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span class="text-success-700">Already uploaded</span>
                                                <span class="text-xs text-neutral-600 ml-auto">Upload again to replace</span>
                                            </div>
                                        @endif
                                        <label class="flex items-center justify-center gap-2 w-full px-4 py-3 border-2 border-dashed border-neutral-300 rounded-lg cursor-pointer hover:border-[#6F9CA2] hover:bg-[#6F9CA2]/5/50 transition-colors">
                                            <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span class="text-sm text-neutral-600" x-text="addressProofName || 'Choose file...'"></span>
                                            <input type="file" name="address_proof" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                                                   @change="addressProofName = $event.target.files[0]?.name || ''">
                                        </label>
                                        @error('address_proof') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="px-5 py-4 bg-neutral-50 border-t border-neutral-100 flex items-center justify-between">
                                    <button type="button" @click="currentStep = 1" class="px-4 py-2.5 text-neutral-600 hover:text-neutral-800 font-medium text-sm transition-colors inline-flex items-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                        Back
                                    </button>
                                    <button type="submit" class="px-6 py-2.5 bg-[#F8931D] hover:bg-[#E07E0A] text-white font-semibold rounded-lg text-sm transition-colors inline-flex items-center gap-2">
                                        Submit Documents
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        <div class="bg-white border border-neutral-100 rounded-xl p-6 text-center">
                            <p class="text-sm text-neutral-600">Please complete Step 1 first.</p>
                            <button @click="currentStep = 1" class="mt-3 text-sm text-[#6F9CA2] hover:text-[#5B878D] font-medium">Go to Step 1</button>
                        </div>
                    @endif
                </div>

                {{-- ============ STEP 3: Verification Status ============ --}}
                <div x-show="currentStep === 3" x-cloak>
                    @if($partner)
                        <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                            <div class="p-6 sm:p-8 text-center">
                                @if($partner->verification_status === 'verified')
                                    <div class="w-16 h-16 bg-success-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <h2 class="text-lg font-bold text-success-700 mb-1">You're Verified!</h2>
                                    <p class="text-sm text-neutral-600 mb-1">Partner ID: <strong>{{ $partner->partner_id }}</strong></p>
                                    <p class="text-sm text-neutral-600 mb-5">Your account is verified and active. You can now access the Delivery Panel to start accepting orders.</p>
                                    <a href="{{ route('delivery.login') }}" class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#F8931D] hover:bg-[#E07E0A] text-white font-semibold rounded-lg text-sm transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                        Go to Delivery Panel
                                    </a>
                                @elseif($partner->verification_status === 'rejected')
                                    <div class="w-16 h-16 bg-error-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-error-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                    </div>
                                    <h2 class="text-lg font-bold text-error-700 mb-1">Verification Rejected</h2>
                                    <p class="text-sm text-neutral-600 mb-1">Partner ID: <strong>{{ $partner->partner_id }}</strong></p>
                                    @if($partner->verification_note)
                                        <div class="mt-3 mb-4 p-3 bg-error-50 border border-error-200 rounded-lg text-sm text-error-700 text-left">
                                            <span class="font-medium">Reason:</span> {{ $partner->verification_note }}
                                        </div>
                                    @endif
                                    <p class="text-sm text-neutral-600 mb-5">Please re-upload your documents to try again.</p>
                                    <button @click="currentStep = 2" class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#F8931D] hover:bg-[#E07E0A] text-white font-semibold rounded-lg text-sm transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Re-upload Documents
                                    </button>
                                @else
                                    {{-- Pending --}}
                                    <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <h2 class="text-lg font-bold text-amber-700 mb-1">Under Review</h2>
                                    <p class="text-sm text-neutral-600 mb-1">Partner ID: <strong>{{ $partner->partner_id }}</strong></p>
                                    <p class="text-sm text-neutral-600 mb-5">Your documents have been submitted and are being reviewed by our team. We'll notify you once the verification is complete.</p>

                                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                                        <button @click="currentStep = 2" class="text-sm text-[#6F9CA2] hover:text-[#5B878D] font-medium inline-flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            Update Documents
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Summary Card --}}
                        <div class="mt-5 bg-white border border-neutral-100 rounded-xl overflow-hidden">
                            <div class="px-5 py-4 border-b border-neutral-100">
                                <h3 class="font-semibold text-neutral-900 text-sm">Registration Summary</h3>
                            </div>
                            <div class="p-5">
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span class="text-neutral-600 text-xs">Partner ID</span>
                                        <p class="font-medium text-neutral-900">{{ $partner->partner_id }}</p>
                                    </div>
                                    <div>
                                        <span class="text-neutral-600 text-xs">Phone</span>
                                        <p class="font-medium text-neutral-900">{{ $partner->phone }}</p>
                                    </div>
                                    <div>
                                        <span class="text-neutral-600 text-xs">Vehicle</span>
                                        <p class="font-medium text-neutral-900 capitalize">{{ $partner->vehicle_type }}</p>
                                    </div>
                                    <div>
                                        <span class="text-neutral-600 text-xs">ID Proof</span>
                                        <p class="font-medium {{ $partner->id_proof ? 'text-success-600' : 'text-error-600' }}">
                                            {{ $partner->id_proof ? 'Uploaded' : 'Not uploaded' }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-neutral-600 text-xs">Driving License</span>
                                        <p class="font-medium {{ $partner->license_document ? 'text-success-600' : 'text-error-600' }}">
                                            {{ $partner->license_document ? 'Uploaded' : 'Not uploaded' }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-neutral-600 text-xs">Verification</span>
                                        <p class="font-medium capitalize
                                            {{ $partner->verification_status === 'verified' ? 'text-success-600' : ($partner->verification_status === 'rejected' ? 'text-error-600' : 'text-amber-600') }}">
                                            {{ $partner->verification_status }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
