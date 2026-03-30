<x-layouts.app>
    <x-slot name="title">My Tickets</x-slot>

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'My Account', 'url' => route('account.dashboard')], ['label' => 'Support Tickets', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-6 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-6">
            @include('account.partials.sidebar')

            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-5">
                    <h1 class="text-xl font-bold text-neutral-900">Support Tickets</h1>
                    <a href="{{ route('account.tickets.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#F8931D] text-white text-sm font-semibold rounded-lg hover:bg-[#E07E0A] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Raise Ticket
                    </a>
                </div>

                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Filter -->
                <div class="flex gap-2 mb-4">
                    <a href="{{ route('account.tickets.index') }}"
                       class="px-3 py-1.5 rounded-full text-xs font-medium {{ !request('status') ? 'bg-[#6F9CA2]/10 text-[#5B878D]' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' }} transition-colors">
                        All
                    </a>
                    <a href="{{ route('account.tickets.index', ['status' => 'open']) }}"
                       class="px-3 py-1.5 rounded-full text-xs font-medium {{ request('status') === 'open' ? 'bg-[#6F9CA2]/10 text-[#5B878D]' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' }} transition-colors">
                        Open
                    </a>
                    <a href="{{ route('account.tickets.index', ['status' => 'answered']) }}"
                       class="px-3 py-1.5 rounded-full text-xs font-medium {{ request('status') === 'answered' ? 'bg-[#6F9CA2]/10 text-[#5B878D]' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' }} transition-colors">
                        Answered
                    </a>
                    <a href="{{ route('account.tickets.index', ['status' => 'closed']) }}"
                       class="px-3 py-1.5 rounded-full text-xs font-medium {{ request('status') === 'closed' ? 'bg-[#6F9CA2]/10 text-[#5B878D]' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' }} transition-colors">
                        Closed
                    </a>
                </div>

                <!-- Tickets List -->
                <div class="space-y-3">
                    @forelse($tickets as $ticket)
                        <a href="{{ route('account.tickets.show', $ticket) }}"
                           class="block bg-white border border-neutral-100 rounded-xl p-4 hover:border-[#6F9CA2]/30 hover:shadow-sm transition-all">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs text-neutral-600">#{{ $ticket->id }}</span>
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
                                        @if($ticket->priority === 'high')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-error-100 text-error-700">High</span>
                                        @endif
                                    </div>
                                    <h3 class="text-sm font-semibold text-neutral-900 truncate">{{ $ticket->subject }}</h3>
                                    <p class="text-xs text-neutral-600 mt-0.5">{{ Str::limit($ticket->message, 80) }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-xs text-neutral-600">{{ $ticket->created_at->format('M d, Y') }}</p>
                                    <p class="text-[10px] text-neutral-600 mt-0.5">{{ $ticket->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="bg-white border border-neutral-100 rounded-xl p-8 text-center">
                            <svg class="w-12 h-12 mx-auto mb-3 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                            <p class="text-sm text-neutral-600 mb-3">No support tickets yet</p>
                            <a href="{{ route('account.tickets.create') }}" class="text-sm text-[#6F9CA2] font-medium hover:underline">Raise your first ticket</a>
                        </div>
                    @endforelse
                </div>

                @if($tickets->hasPages())
                    <div class="mt-4">
                        {{ $tickets->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
