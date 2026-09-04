<x-layouts.app>
    <x-slot name="title">Raise a Ticket</x-slot>

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'My Account', 'url' => route('account.dashboard')], ['label' => 'Support Tickets', 'url' => route('account.tickets.index')], ['label' => 'Raise Ticket', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-6 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-6">
            @include('account.partials.sidebar')

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-5">
                    <a href="{{ route('account.tickets.index') }}" class="inline-flex p-2.5 -m-2.5 shrink-0 text-neutral-600 hover:text-neutral-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <h1 class="text-xl font-bold text-neutral-900">Raise a Ticket</h1>
                </div>

                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-7">
                    {{-- An in-flight guard on the form itself, not only the site-wide one in
                         app.js, because this page has to SAY it is working. TicketController::store
                         has no duplicate check of its own and notifies every admin as it writes, so
                         two clicks on Submit Ticket opened two identical tickets and sent the
                         support team two rounds of notifications - leaving the customer holding two
                         numbers for one problem. app.js does refuse the second submission, but it
                         has no styling to go with it, so the button carried on looking live for the
                         length of the round trip, which is exactly what earns the second click.

                         Raised from @submit, which the browser fires only once its own required and
                         minlength checks have passed, so a submission stopped by client-side
                         validation leaves the button usable for the correction. Lowered again on a
                         persisted pageshow, or a visitor arriving back here through the bfcache
                         would find a form whose only button is permanently dead. --}}
                    <form action="{{ route('account.tickets.store') }}" method="POST" class="space-y-5"
                          x-data="{ submitting: false }"
                          @submit="submitting = true"
                          @pageshow.window="if ($event.persisted) submitting = false">
                        @csrf

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="category" class="block text-sm font-medium text-neutral-700 mb-1.5">Category <span class="text-red-400">*</span></label>
                                <select name="category" id="category" required
                                        class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all">
                                    <option value="">Select category</option>
                                    <option value="general" @selected(old('category') === 'general')>General Inquiry</option>
                                    <option value="order" @selected(old('category') === 'order')>Order Issue</option>
                                    <option value="payment" @selected(old('category') === 'payment')>Payment Issue</option>
                                    <option value="product" @selected(old('category') === 'product')>Product Query</option>
                                    <option value="account" @selected(old('category') === 'account')>Account Issue</option>
                                    <option value="other" @selected(old('category') === 'other')>Other</option>
                                </select>
                                <x-field-error field="category" />
                            </div>

                            <div>
                                <label for="priority" class="block text-sm font-medium text-neutral-700 mb-1.5">Priority <span class="text-red-400">*</span></label>
                                <select name="priority" id="priority" required
                                        class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all">
                                    <option value="low" @selected(old('priority', 'normal') === 'low')>Low</option>
                                    <option value="normal" @selected(old('priority', 'normal') === 'normal')>Normal</option>
                                    <option value="high" @selected(old('priority') === 'high')>High</option>
                                </select>
                                <x-field-error field="priority" />
                            </div>
                        </div>

                        <div>
                            <label for="subject" class="block text-sm font-medium text-neutral-700 mb-1.5">Subject <span class="text-red-400">*</span></label>
                            <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required
                                   minlength="3" maxlength="255"
                                   title="Summarise the issue in a few words."
                                   class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all"
                                   placeholder="Brief description of your issue">
                            <x-field-error field="subject" />
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-neutral-700 mb-1.5">Message <span class="text-red-400">*</span></label>
                            {{-- minlength 10 is the server's own min:10 - previously a one-word
                                 message was accepted by the box and refused by the controller. --}}
                            <textarea name="message" id="message" rows="6" required
                                      minlength="10" maxlength="5000"
                                      class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all resize-none"
                                      placeholder="Describe your issue in detail...">{{ old('message') }}</textarea>
                            <x-field-error field="message" />
                        </div>

                        <div class="flex flex-wrap items-center gap-3 pt-1">
                            <button type="submit"
                                    :disabled="submitting"
                                    :aria-busy="submitting ? 'true' : 'false'"
                                    :class="submitting && 'opacity-60 cursor-not-allowed'"
                                    class="px-6 py-2.5 bg-gradient-to-r from-[#F8931D] to-[#E07E0A] hover:from-[#E07E0A] hover:to-[#D47200] text-white text-sm font-semibold rounded-xl shadow-lg shadow-[#F8931D]/25 transition-all">
                                {{-- x-text over the wording Blade already printed, so the label is
                                     right before Alpine boots and stays right if it never does. --}}
                                <span x-text="submitting ? 'Submitting...' : 'Submit Ticket'">Submit Ticket</span>
                            </button>
                            <a href="{{ route('account.tickets.index') }}" class="px-4 py-2.5 text-sm text-neutral-600 hover:text-neutral-900">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
