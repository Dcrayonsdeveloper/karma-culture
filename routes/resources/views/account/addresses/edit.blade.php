<x-layouts.app>
    <x-slot name="title">Edit Address</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Sidebar -->
                @include('account.partials.sidebar')

                <!-- Main Content -->
                <div class="flex-1 max-w-2xl">
                    <!-- Breadcrumb -->
                    <nav class="flex items-center gap-1.5 text-[13px] text-neutral-600 mb-5">
                        <a href="{{ route('account.addresses.index') }}" class="hover:text-primary-600 transition-colors">Addresses</a>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="text-neutral-600">Edit</span>
                    </nav>

                    <!-- Header -->
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-neutral-900">Edit Address</h1>
                            <p class="text-[13px] text-neutral-600">Update your delivery address details</p>
                        </div>
                    </div>

                    <form action="{{ route('account.addresses.update', $address) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Address Label -->
                        @php $currentLabel = old('label', $address->label ?? 'home'); @endphp
                        <div class="bg-white rounded-xl border border-neutral-100 p-5 mb-4" x-data="{ label: '{{ $currentLabel }}' }">
                            <h2 class="text-sm font-semibold text-neutral-900 mb-3">Address Label</h2>
                            <input type="hidden" name="label" :value="label">
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="label = 'home'"
                                        :class="label === 'home' ? 'bg-primary-50 border-primary-500 text-primary-700' : 'border-neutral-200 text-neutral-600 hover:border-neutral-300'"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border text-sm font-medium transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                    Home
                                </button>
                                <button type="button" @click="label = 'office'"
                                        :class="label === 'office' ? 'bg-primary-50 border-primary-500 text-primary-700' : 'border-neutral-200 text-neutral-600 hover:border-neutral-300'"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border text-sm font-medium transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    Office
                                </button>
                                <button type="button" @click="label = 'other'"
                                        :class="label === 'other' ? 'bg-primary-50 border-primary-500 text-primary-700' : 'border-neutral-200 text-neutral-600 hover:border-neutral-300'"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border text-sm font-medium transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Other
                                </button>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="bg-white rounded-xl border border-neutral-100 p-5 mb-4">
                            <h2 class="text-sm font-semibold text-neutral-900 mb-4">Contact Information</h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="name" class="block text-[13px] font-medium text-neutral-600 mb-1.5">Full Name <span class="text-error-500">*</span></label>
                                    <input type="text" name="name" id="name" value="{{ old('name', $address->full_name) }}" required
                                           class="w-full rounded-lg border border-neutral-200 px-3.5 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors @error('name') border-error-300 ring-1 ring-error-300 @enderror"
                                           placeholder="Enter full name">
                                    @error('name')
                                        <p class="mt-1 text-[12px] text-error-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="phone" class="block text-[13px] font-medium text-neutral-600 mb-1.5">Phone Number <span class="text-error-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-neutral-600">+91</span>
                                        <input type="tel" name="phone" id="phone" value="{{ old('phone', $address->phone) }}" required
                                               maxlength="10" pattern="[6-9][0-9]{9}"
                                               class="w-full rounded-lg border border-neutral-200 pl-12 pr-3.5 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors @error('phone') border-error-300 ring-1 ring-error-300 @enderror"
                                               placeholder="10-digit mobile number">
                                    </div>
                                    @error('phone')
                                        <p class="mt-1 text-[12px] text-error-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Address Details -->
                        <div class="bg-white rounded-xl border border-neutral-100 p-5 mb-4">
                            <h2 class="text-sm font-semibold text-neutral-900 mb-4">Address Details</h2>

                            <div class="space-y-4">
                                <div>
                                    <label for="address_line1" class="block text-[13px] font-medium text-neutral-600 mb-1.5">Address Line 1 <span class="text-error-500">*</span></label>
                                    <input type="text" name="address_line1" id="address_line1" value="{{ old('address_line1', $address->address_line_1) }}" required
                                           class="w-full rounded-lg border border-neutral-200 px-3.5 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors @error('address_line1') border-error-300 ring-1 ring-error-300 @enderror"
                                           placeholder="House/Flat no., Building, Street">
                                    @error('address_line1')
                                        <p class="mt-1 text-[12px] text-error-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="address_line2" class="block text-[13px] font-medium text-neutral-600 mb-1.5">Address Line 2 <span class="text-neutral-600 font-normal">(Optional)</span></label>
                                    <input type="text" name="address_line2" id="address_line2" value="{{ old('address_line2', $address->address_line_2) }}"
                                           class="w-full rounded-lg border border-neutral-200 px-3.5 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors @error('address_line2') border-error-300 ring-1 ring-error-300 @enderror"
                                           placeholder="Area, Colony, Landmark">
                                    @error('address_line2')
                                        <p class="mt-1 text-[12px] text-error-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="city" class="block text-[13px] font-medium text-neutral-600 mb-1.5">City <span class="text-error-500">*</span></label>
                                        <input type="text" name="city" id="city" value="{{ old('city', $address->city) }}" required
                                               class="w-full rounded-lg border border-neutral-200 px-3.5 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors @error('city') border-error-300 ring-1 ring-error-300 @enderror"
                                               placeholder="Enter city">
                                        @error('city')
                                            <p class="mt-1 text-[12px] text-error-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="state" class="block text-[13px] font-medium text-neutral-600 mb-1.5">State <span class="text-error-500">*</span></label>
                                        <select name="state" id="state" required
                                                class="w-full rounded-lg border border-neutral-200 px-3.5 py-2.5 text-sm text-neutral-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors @error('state') border-error-300 ring-1 ring-error-300 @enderror">
                                            <option value="">Select state</option>
                                            @php
                                                $states = [
                                                    'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
                                                    'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand',
                                                    'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur',
                                                    'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
                                                    'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
                                                    'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
                                                    'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu',
                                                    'Delhi', 'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry',
                                                ];
                                            @endphp
                                            @foreach ($states as $state)
                                                <option value="{{ $state }}" {{ old('state', $address->state) === $state ? 'selected' : '' }}>{{ $state }}</option>
                                            @endforeach
                                        </select>
                                        @error('state')
                                            <p class="mt-1 text-[12px] text-error-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="postal_code" class="block text-[13px] font-medium text-neutral-600 mb-1.5">PIN Code <span class="text-error-500">*</span></label>
                                        <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $address->postal_code) }}" required
                                               maxlength="6" pattern="[0-9]{6}"
                                               class="w-full rounded-lg border border-neutral-200 px-3.5 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors @error('postal_code') border-error-300 ring-1 ring-error-300 @enderror"
                                               placeholder="6-digit PIN code">
                                        @error('postal_code')
                                            <p class="mt-1 text-[12px] text-error-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="country" class="block text-[13px] font-medium text-neutral-600 mb-1.5">Country <span class="text-error-500">*</span></label>
                                        <select name="country" id="country" required
                                                class="w-full rounded-lg border border-neutral-200 px-3.5 py-2.5 text-sm text-neutral-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors @error('country') border-error-300 ring-1 ring-error-300 @enderror">
                                            <option value="IN" {{ old('country', $address->country) === 'IN' ? 'selected' : '' }}>India</option>
                                        </select>
                                        @error('country')
                                            <p class="mt-1 text-[12px] text-error-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Settings -->
                        <div class="bg-white rounded-xl border border-neutral-100 p-5 mb-6">
                            <h2 class="text-sm font-semibold text-neutral-900 mb-3">Settings</h2>

                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" name="is_default" value="1" {{ old('is_default', $address->is_default) ? 'checked' : '' }}
                                           class="w-5 h-5 rounded border-neutral-300 text-primary-600 focus:ring-primary-500 focus:ring-offset-0 transition-colors">
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-neutral-700 group-hover:text-neutral-900 transition-colors">Set as default address</span>
                                    <p class="text-[12px] text-neutral-600">This address will be pre-selected at checkout</p>
                                </div>
                            </label>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Update Address
                            </button>
                            <a href="{{ route('account.addresses.index') }}"
                               class="inline-flex items-center px-5 py-2.5 border border-neutral-200 text-sm font-medium text-neutral-600 rounded-lg hover:bg-neutral-50 hover:border-neutral-300 transition-all">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
