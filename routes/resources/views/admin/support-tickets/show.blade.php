<x-layouts.admin>
    <x-slot name="title">Ticket #{{ $supportTicket->id }}</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.support-tickets.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Support Tickets
        </a>
    </div>
    <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem;">
        <div>
            <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Ticket #{{ $supportTicket->id }}</h1>
            <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 0 0;">{{ $supportTicket->created_at->format('M d, Y h:i A') }}</p>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <form action="{{ route('admin.support-tickets.status', $supportTicket) }}" method="POST" style="display: flex; align-items: center; gap: 0.5rem;">
                @csrf
                @method('PUT')
                <select name="status" class="form-select" style="font-size: 13px;">
                    <option value="open" @selected($supportTicket->status === 'open')>Open</option>
                    <option value="answered" @selected($supportTicket->status === 'answered')>Answered</option>
                    <option value="closed" @selected($supportTicket->status === 'closed')>Closed</option>
                </select>
                <button type="submit" class="btn btn-secondary" style="font-size: 13px;">Update</button>
            </form>
            <form action="{{ route('admin.support-tickets.destroy', $supportTicket) }}" method="POST"
                  onsubmit="return confirm('Delete this ticket?')">
                @csrf
                @method('DELETE')
                <button type="submit" style="font-size: 13px; font-weight: 500; color: #d72c0d; cursor: pointer; background: none; border: 1px solid #e3e3e3; padding: 0.375rem 0.75rem; border-radius: 0.5rem;">Delete</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #cdfee1; border: 1px solid #1a7a2e; border-radius: 0.5rem; font-size: 13px; color: #1a7a2e;">
            {{ session('success') }}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
        <!-- Conversation -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Original Message -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">{{ $supportTicket->subject }}</h2>
                </div>
                <div style="padding: 1rem;">
                    <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                        <div style="width: 2rem; height: 2rem; background: #d4edfc; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span style="font-size: 11px; font-weight: 600; color: #0064a4;">{{ strtoupper(substr($supportTicket->user->first_name, 0, 1)) }}</span>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <span style="font-size: 13px; font-weight: 500; color: #303030;">{{ $supportTicket->user->full_name }}</span>
                                <span style="font-size: 12px; color: #616161;">{{ $supportTicket->created_at->diffForHumans() }}</span>
                            </div>
                            <div style="font-size: 13px; color: #303030; line-height: 1.6;">
                                {!! nl2br(e($supportTicket->message)) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Replies -->
            @foreach($supportTicket->replies as $reply)
                <div class="card" style="{{ $reply->is_admin ? 'border-left: 4px solid #005bd3;' : '' }}">
                    <div style="padding: 1rem;">
                        <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                            <div style="width: 2rem; height: 2rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; {{ $reply->is_admin ? 'background: #005bd3;' : 'background: #d4edfc;' }}">
                                <span style="font-size: 11px; font-weight: 600; {{ $reply->is_admin ? 'color: white;' : 'color: #0064a4;' }}">
                                    {{ strtoupper(substr($reply->user->first_name ?? 'A', 0, 1)) }}
                                </span>
                            </div>
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                    <span style="font-size: 13px; font-weight: 500; color: #303030;">{{ $reply->user->full_name ?? 'Admin' }}</span>
                                    @if($reply->is_admin)
                                        <span style="display: inline-block; padding: 0.0625rem 0.375rem; border-radius: 0.25rem; font-size: 10px; font-weight: 500; background: #d4edfc; color: #0064a4;">Staff</span>
                                    @endif
                                    <span style="font-size: 12px; color: #616161;">{{ $reply->created_at->diffForHumans() }}</span>
                                </div>
                                <div style="font-size: 13px; color: #303030; line-height: 1.6;">
                                    {!! nl2br(e($reply->message)) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Admin Reply Form -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Reply to Customer</h3>
                </div>
                <form action="{{ route('admin.support-tickets.reply', $supportTicket) }}" method="POST">
                    @csrf
                    <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                        <textarea name="message" rows="4" required
                                  class="form-input" style="width: 100%;"
                                  placeholder="Type your reply to the customer...">{{ old('message') }}</textarea>
                        @error('message')
                            <p style="font-size: 13px; color: #d72c0d; margin: 0;">{{ $message }}</p>
                        @enderror
                        <div style="display: flex; justify-content: flex-end;">
                            <button type="submit" class="btn btn-primary" style="font-size: 13px;">Send Reply</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Customer Details -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Customer</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 2.5rem; height: 2.5rem; background: #d4edfc; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span style="font-size: 13px; font-weight: 600; color: #0064a4;">{{ strtoupper(substr($supportTicket->user->first_name, 0, 1)) }}</span>
                        </div>
                        <div>
                            <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">{{ $supportTicket->user->full_name }}</p>
                            <p style="font-size: 12px; color: #616161; margin: 0;">{{ $supportTicket->user->email }}</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.customers.show', $supportTicket->user) }}" style="font-size: 12px; color: #005bd3; text-decoration: none;">View Customer Profile</a>
                </div>
            </div>

            <!-- Ticket Info -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Ticket Details</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px; align-items: center;">
                        <span style="color: #616161;">Status</span>
                        @switch($supportTicket->status)
                            @case('open')
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #fff3cd; color: #8a6d00;">Open</span>
                                @break
                            @case('answered')
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Answered</span>
                                @break
                            @case('closed')
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ebebeb; color: #616161;">Closed</span>
                                @break
                        @endswitch
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; align-items: center;">
                        <span style="color: #616161;">Priority</span>
                        @switch($supportTicket->priority)
                            @case('high')
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">High</span>
                                @break
                            @case('normal')
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #d4edfc; color: #0064a4;">Normal</span>
                                @break
                            @case('low')
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ebebeb; color: #616161;">Low</span>
                                @break
                        @endswitch
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Category</span>
                        <span style="font-weight: 500; color: #303030; text-transform: capitalize;">{{ $supportTicket->category }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Created</span>
                        <span style="font-weight: 500; color: #303030;">{{ $supportTicket->created_at->format('M d, Y') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Replies</span>
                        <span style="font-weight: 500; color: #303030;">{{ $supportTicket->replies->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
