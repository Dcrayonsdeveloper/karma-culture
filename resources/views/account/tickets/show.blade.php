<x-layouts.app>
    <x-slot name="title">Ticket #{{ $ticket->id }}</x-slot>

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'My Account', 'url' => route('account.dashboard')], ['label' => 'Support Tickets', 'url' => route('account.tickets.index')], ['label' => '#' . $ticket->id, 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-6 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-6">
            @include('account.partials.sidebar')

            <div class="flex-1 min-w-0">
                <!-- Header -->
                <div class="flex items-start justify-between mb-5">
                    <div class="flex items-center gap-3 min-w-0">
                        <a href="{{ route('account.tickets.index') }}" class="inline-flex p-2.5 -m-2.5 shrink-0 text-neutral-600 hover:text-neutral-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-xl font-bold text-neutral-900">Ticket #{{ $ticket->id }}</h1>
                                @switch($ticket->status)
                                    @case('open')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-warning-100 text-warning-700">Open</span>
                                        @break
                                    @case('answered')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-success-100 text-success-700">Answered</span>
                                        @break
                                    @case('closed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-neutral-100 text-neutral-600">Closed</span>
                                        @break
                                @endswitch
                            </div>
                            <p class="text-xs text-neutral-600 mt-0.5">{{ $ticket->created_at->format('M d, Y h:i A') }} &middot; {{ ucfirst($ticket->category) }} &middot; {{ ucfirst($ticket->priority) }} priority</p>
                        </div>
                    </div>
                </div>

                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Original Message -->
                <div class="bg-white border border-neutral-100 rounded-xl mb-4">
                    <div class="px-5 py-3 border-b border-neutral-100">
                        <h2 class="text-sm font-semibold text-neutral-900">{{ $ticket->subject }}</h2>
                    </div>
                    <div class="p-5">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-[#6F9CA2]/10 rounded-full flex items-center justify-center shrink-0">
                                <span class="text-xs font-semibold text-[#6F9CA2]">{{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-sm font-medium text-neutral-900">You</span>
                                    <span class="text-xs text-neutral-600">{{ $ticket->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="text-sm text-neutral-700 leading-relaxed">
                                    {!! nl2br(e($ticket->message)) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Replies -->
                @foreach($ticket->replies as $reply)
                    <div class="bg-white border border-neutral-100 rounded-xl mb-4 {{ $reply->is_admin ? 'border-l-4 border-l-[#6F9CA2]' : '' }}">
                        <div class="p-5">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $reply->is_admin ? 'bg-[#F8931D]' : 'bg-[#6F9CA2]/10' }}">
                                    <span class="text-xs font-semibold {{ $reply->is_admin ? 'text-white' : 'text-[#6F9CA2]' }}">
                                        {{ $reply->is_admin ? 'S' : strtoupper(substr(auth()->user()->first_name, 0, 1)) }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-medium text-neutral-900">{{ $reply->is_admin ? 'Support Team' : 'You' }}</span>
                                        @if($reply->is_admin)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-[#6F9CA2]/10 text-[#5B878D]">Staff</span>
                                        @endif
                                        <span class="text-xs text-neutral-600">{{ $reply->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="text-sm text-neutral-700 leading-relaxed">
                                        {!! nl2br(e($reply->message)) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Reply Form -->
                {{-- The form-level line for this page, and it sits ABOVE the @if on purpose so
                     that it renders in both branches. The reply box - and with it the only
                     <x-field-error field="message"> on the page - lives inside the @if, so a
                     ticket that support closed between this page being rendered and the reply
                     being posted came back to a page with nowhere to print the controller's
                     answer: the error bag was flashed and then dropped unread, and the customer
                     was shown the same static "closed" panel he would have seen anyway, with
                     nothing on it saying that the reply he had just written was refused. An
                     error whose field is not on the page is precisely the orphan a form-level
                     banner exists for.

                     `handled` names the one key that IS rendered inline below, so a reply that
                     is merely too short is never complained about twice - once here and once
                     under the box. `for` is spelt out because in the closed branch there is no
                     reply form to be "the next form in document order", and app.js would then
                     hand this banner to the first form further down the layout - a popup's
                     newsletter box - which would retire the message the moment THAT was
                     submitted. --}}
                <x-form-errors :handled="['message']" for="ticket-reply-form" title="Your reply could not be sent." />

                @if($ticket->status !== 'closed')
                    <div class="bg-white border border-neutral-100 rounded-xl">
                        <div class="px-5 py-3 border-b border-neutral-100">
                            <h3 class="text-sm font-semibold text-neutral-900">Reply</h3>
                        </div>
                        {{-- The same in-flight guard the Raise a Ticket form carries, for the
                             same reason: TicketController::reply writes the row and reopens the
                             ticket without ever asking whether it has seen this message before, so a
                             second click - or an Enter on top of a click - posted the reply twice
                             and put one paragraph into the thread under two timestamps, which then
                             reads to the support team as a customer repeating himself. app.js
                             already refuses the second submission, but it cannot style the button
                             while it does; a control that still looks live for the length of the
                             round trip is what earns that second click in the first place.

                             Raised from @submit, which fires only once the browser's required and
                             minlength checks on the box above have passed, so a reply rejected for
                             being too short leaves the button usable. Lowered on a persisted
                             pageshow, so a thread reached with the back button is not left with a
                             reply box that can no longer be sent. --}}
                        <form action="{{ route('account.tickets.reply', $ticket) }}" method="POST" class="p-5"
                              id="ticket-reply-form"
                              x-data="{ submitting: false }"
                              @submit="submitting = true"
                              @pageshow.window="if ($event.persisted) submitting = false">
                            @csrf
                            {{-- aria-label, so the inline error reads "Reply is required" rather
                                 than falling back to the placeholder text. --}}
                            <textarea name="message" id="reply_message" rows="4" required
                                      minlength="5" maxlength="5000" aria-label="Reply"
                                      class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all resize-none"
                                      placeholder="Type your reply...">{{ old('message') }}</textarea>
                            <x-field-error field="message" />
                            <div class="mt-3 flex justify-end">
                                <button type="submit"
                                        :disabled="submitting"
                                        :aria-busy="submitting ? 'true' : 'false'"
                                        :class="submitting && 'opacity-60 cursor-not-allowed'"
                                        class="px-5 py-2 bg-[#F8931D] text-white text-sm font-semibold rounded-lg hover:bg-[#E07E0A] transition-colors">
                                    <span x-text="submitting ? 'Sending...' : 'Send Reply'">Send Reply</span>
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-4 text-center text-sm text-neutral-600">
                        This ticket is closed. If you need further help, please raise a new ticket.
                    </div>

                    {{-- What the customer had already typed when the ticket turned out to be
                         closed. TicketController::reply keeps it with withInput() precisely so
                         that it is not lost, and until now that was dead weight: the textarea it
                         would have flowed back into is inside the branch above, which is not
                         rendered here, so the reply went into the redirect and was never seen
                         again. Read-only, unnamed and outside any form - it is a copy of
                         something that was NOT sent, shown so it can be pasted into the new
                         ticket instead of being written a second time. --}}
                    @if(old('message'))
                        <div class="mt-4 bg-white border border-neutral-100 rounded-xl p-5">
                            <h3 class="text-sm font-semibold text-neutral-900">The reply you had typed</h3>
                            <p class="text-xs text-neutral-600 mt-0.5 mb-3">It was not sent. Copy it into your new ticket so you don't have to write it out again.</p>
                            <textarea rows="4" readonly aria-label="The reply you had typed"
                                      class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-700 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all resize-none">{{ old('message') }}</textarea>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
