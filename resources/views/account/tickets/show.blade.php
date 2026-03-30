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
                    <div class="flex items-center gap-3">
                        <a href="{{ route('account.tickets.index') }}" class="text-neutral-600 hover:text-neutral-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        <div>
                            <div class="flex items-center gap-2">
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
                @if($ticket->status !== 'closed')
                    <div class="bg-white border border-neutral-100 rounded-xl">
                        <div class="px-5 py-3 border-b border-neutral-100">
                            <h3 class="text-sm font-semibold text-neutral-900">Reply</h3>
                        </div>
                        <form action="{{ route('account.tickets.reply', $ticket) }}" method="POST" class="p-5">
                            @csrf
                            <textarea name="message" rows="4" required
                                      class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all resize-none @error('message') border-red-300 @enderror"
                                      placeholder="Type your reply...">{{ old('message') }}</textarea>
                            @error('message')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <div class="mt-3 flex justify-end">
                                <button type="submit"
                                        class="px-5 py-2 bg-[#F8931D] text-white text-sm font-semibold rounded-lg hover:bg-[#E07E0A] transition-colors">
                                    Send Reply
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-4 text-center text-sm text-neutral-600">
                        This ticket is closed. If you need further help, please raise a new ticket.
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
