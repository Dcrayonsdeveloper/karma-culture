<x-layouts.app>
    <x-slot name="title">My Addresses</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <x-breadcrumb :items="[['label' => 'Account', 'url' => route('account.dashboard')], ['label' => 'Addresses']]" />
            <div class="flex flex-col lg:flex-row gap-8 mt-4">
                <!-- Sidebar -->
                @include('account.partials.sidebar')

                <!-- Main Content -->
                <div class="flex-1">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-neutral-900">My Addresses</h1>
                                <p class="text-[13px] text-neutral-600">{{ $addresses->count() }} saved {{ Str::plural('address', $addresses->count()) }}</p>
                            </div>
                        </div>
                        <a href="{{ route('account.addresses.create') }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Address
                        </a>
                    </div>

                    @if($addresses->count())
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($addresses as $address)
                                <div class="bg-white rounded-xl border {{ $address->is_default ? 'border-primary-200 ring-1 ring-primary-100' : 'border-neutral-100' }} p-5 relative group hover:shadow-sm transition-all">
                                    <!-- Label & Default Badge -->
                                    <div class="flex items-center gap-2 mb-3">
                                        @if($address->label)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-neutral-100 text-[12px] font-medium text-neutral-600 capitalize">
                                                @if($address->label === 'home')
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                                    </svg>
                                                @elseif($address->label === 'office')
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                    </svg>
                                                @endif
                                                {{ $address->label }}
                                            </span>
                                        @endif
                                        @if($address->is_default)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md bg-primary-50 text-[12px] font-semibold text-primary-700">Default</span>
                                        @endif
                                    </div>

                                    <!-- Name & Phone -->
                                    <h3 class="text-sm font-semibold text-neutral-900">{{ $address->full_name }}</h3>
                                    <p class="text-[13px] text-neutral-600 mt-0.5">{{ $address->phone }}</p>

                                    <!-- Address -->
                                    <p class="text-[13px] text-neutral-600 mt-2 leading-relaxed">
                                        {{ $address->address_line_1 }}@if($address->address_line_2), {{ $address->address_line_2 }}@endif<br>
                                        {{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}
                                    </p>

                                    <!-- Actions -->
                                    <div class="flex items-center gap-3 mt-4 pt-3.5 border-t border-neutral-100">
                                        <a href="{{ route('account.addresses.edit', $address) }}"
                                           class="inline-flex items-center gap-1 text-[13px] font-medium text-primary-600 hover:text-primary-700 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </a>
                                        @if(!$address->is_default)
                                            <form action="{{ route('account.addresses.update', $address) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="name" value="{{ $address->full_name }}">
                                                <input type="hidden" name="phone" value="{{ $address->phone }}">
                                                <input type="hidden" name="address_line1" value="{{ $address->address_line_1 }}">
                                                <input type="hidden" name="address_line2" value="{{ $address->address_line_2 }}">
                                                <input type="hidden" name="city" value="{{ $address->city }}">
                                                <input type="hidden" name="state" value="{{ $address->state }}">
                                                <input type="hidden" name="postal_code" value="{{ $address->postal_code }}">
                                                <input type="hidden" name="country" value="{{ $address->country }}">
                                                <input type="hidden" name="is_default" value="1">
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1 text-[13px] font-medium text-neutral-600 hover:text-primary-600 transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    Set Default
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('account.addresses.destroy', $address) }}" method="POST"
                                              onsubmit="return confirm('Delete this address?')" class="inline ml-auto">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 text-[13px] font-medium text-neutral-600 hover:text-error-600 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-white rounded-xl border border-neutral-100 p-12 text-center">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-neutral-100 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-neutral-900 mb-1">No addresses saved</h3>
                            <p class="text-[13px] text-neutral-600 mb-5">Add an address to make checkout faster</p>
                            <a href="{{ route('account.addresses.create') }}"
                               class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Your First Address
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
