<x-layouts.app>
    <x-slot name="title">Profile Settings</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <x-breadcrumb :items="[['label' => 'Account', 'url' => route('account.dashboard')], ['label' => 'Profile Settings']]" />
            <div class="flex flex-col lg:flex-row gap-8 mt-4">
                @include('account.partials.sidebar')

                <div class="flex-1 max-w-2xl">
                    <h1 class="text-xl font-bold text-neutral-900 mb-5">Profile Settings</h1>

                    @if(session('success'))
                        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-700 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ session('success') }}
                        </div>
                    @endif

                    {{-- Avatar + Name Header --}}
                    <div class="bg-white rounded-xl border border-neutral-200 p-5 mb-4">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full bg-[#6F9CA2]/10 flex items-center justify-center shrink-0">
                                <span class="text-xl font-bold text-[#6F9CA2]">{{ strtoupper(substr($user->first_name, 0, 1)) }}{{ strtoupper(substr($user->last_name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-neutral-900">{{ $user->first_name }} {{ $user->last_name }}</h2>
                                <p class="text-sm text-neutral-600">{{ $user->email }}</p>
                                <p class="text-xs text-neutral-600 mt-0.5">Member since {{ $user->created_at->format('F Y') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Personal Information --}}
                    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden mb-4">
                        <div class="px-5 py-3 border-b border-neutral-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <h2 class="text-sm font-bold text-neutral-900">Personal Information</h2>
                        </div>

                        <form action="{{ route('account.profile.update') }}" method="POST" class="p-5">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="first_name" class="block text-xs font-medium text-neutral-600 mb-1">First Name</label>
                                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name) }}" required
                                           class="w-full rounded-lg border {{ $errors->has('first_name') ? 'border-red-300' : 'border-neutral-200' }} text-sm px-3 py-2.5 focus:border-[#6F9CA2]/50 focus:ring focus:ring-[#6F9CA2]/15 focus:ring-opacity-50">
                                    @error('first_name')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="last_name" class="block text-xs font-medium text-neutral-600 mb-1">Last Name</label>
                                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name) }}" required
                                           class="w-full rounded-lg border {{ $errors->has('last_name') ? 'border-red-300' : 'border-neutral-200' }} text-sm px-3 py-2.5 focus:border-[#6F9CA2]/50 focus:ring focus:ring-[#6F9CA2]/15 focus:ring-opacity-50">
                                    @error('last_name')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="email" class="block text-xs font-medium text-neutral-600 mb-1">Email Address</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </div>
                                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                                           class="w-full rounded-lg border {{ $errors->has('email') ? 'border-red-300' : 'border-neutral-200' }} text-sm pl-9 pr-3 py-2.5 focus:border-[#6F9CA2]/50 focus:ring focus:ring-[#6F9CA2]/15 focus:ring-opacity-50">
                                </div>
                                @error('email')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-5">
                                <label for="phone" class="block text-xs font-medium text-neutral-600 mb-1">Phone Number</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-xs text-neutral-600 font-medium">+91</span>
                                    </div>
                                    <input type="tel" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                                           placeholder="9876543210"
                                           class="w-full rounded-lg border {{ $errors->has('phone') ? 'border-red-300' : 'border-neutral-200' }} text-sm pl-11 pr-3 py-2.5 focus:border-[#6F9CA2]/50 focus:ring focus:ring-[#6F9CA2]/15 focus:ring-opacity-50">
                                </div>
                                @error('phone')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit"
                                    class="inline-flex items-center gap-2 bg-[#F8931D] hover:bg-[#E07E0A] text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors">
                                Save Changes
                            </button>
                        </form>
                    </div>

                    {{-- Change Password --}}
                    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden"
                         x-data="{ showPassword: false, showNew: false, showConfirm: false }">
                        <div class="px-5 py-3 border-b border-neutral-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <h2 class="text-sm font-bold text-neutral-900">Change Password</h2>
                        </div>

                        <form action="{{ route('account.password.update') }}" method="POST" class="p-5">
                            @csrf
                            @method('PUT')

                            <div class="space-y-4 mb-5">
                                <div>
                                    <label for="current_password" class="block text-xs font-medium text-neutral-600 mb-1">Current Password</label>
                                    <div class="relative">
                                        <input :type="showPassword ? 'text' : 'password'" name="current_password" id="current_password" required
                                               class="w-full rounded-lg border {{ $errors->has('current_password') ? 'border-red-300' : 'border-neutral-200' }} text-sm px-3 py-2.5 pr-10 focus:border-[#6F9CA2]/50 focus:ring focus:ring-[#6F9CA2]/15 focus:ring-opacity-50">
                                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-neutral-600 hover:text-neutral-600">
                                            <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        </button>
                                    </div>
                                    @error('current_password')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password" class="block text-xs font-medium text-neutral-600 mb-1">New Password</label>
                                    <div class="relative">
                                        <input :type="showNew ? 'text' : 'password'" name="password" id="password" required
                                               class="w-full rounded-lg border {{ $errors->has('password') ? 'border-red-300' : 'border-neutral-200' }} text-sm px-3 py-2.5 pr-10 focus:border-[#6F9CA2]/50 focus:ring focus:ring-[#6F9CA2]/15 focus:ring-opacity-50">
                                        <button type="button" @click="showNew = !showNew" class="absolute inset-y-0 right-0 pr-3 flex items-center text-neutral-600 hover:text-neutral-600">
                                            <svg x-show="!showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg x-show="showNew" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        </button>
                                    </div>
                                    @error('password')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password_confirmation" class="block text-xs font-medium text-neutral-600 mb-1">Confirm New Password</label>
                                    <div class="relative">
                                        <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" id="password_confirmation" required
                                               class="w-full rounded-lg border border-neutral-200 text-sm px-3 py-2.5 pr-10 focus:border-[#6F9CA2]/50 focus:ring focus:ring-[#6F9CA2]/15 focus:ring-opacity-50">
                                        <button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 pr-3 flex items-center text-neutral-600 hover:text-neutral-600">
                                            <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg x-show="showConfirm" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button type="submit"
                                    class="inline-flex items-center gap-2 bg-neutral-900 hover:bg-neutral-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
