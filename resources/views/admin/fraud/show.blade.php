<x-layouts.admin>
    <x-slot name="title">Fraud Case #{{ $fraudLog->id }}</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.fraud.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Fraud Review
        </a>
    </div>
    <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0 0 1rem 0;">Fraud Case #{{ $fraudLog->id }}</h1>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
        <!-- Main Content -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Order Details -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Order Details</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    @if($fraudLog->order)
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Order Number</span>
                            <a href="{{ route('admin.orders.show', $fraudLog->order_id) }}" style="font-weight: 500; color: #005bd3; text-decoration: none;">
                                {{ $fraudLog->order->order_number }}
                            </a>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Order Date</span>
                            <span style="font-weight: 500; color: #303030;">{{ $fraudLog->order->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Order Total</span>
                            <span style="font-weight: 500; color: #303030;">@price($fraudLog->order->total)</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Payment Method</span>
                            <span style="font-weight: 500; color: #303030;">{{ ucfirst($fraudLog->order->payment_method ?? '-') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Order Status</span>
                            <span style="font-weight: 500; color: #303030;">{{ ucfirst($fraudLog->order->status ?? '-') }}</span>
                        </div>
                    @else
                        <p style="font-size: 13px; color: #616161; margin: 0;">Order has been deleted</p>
                    @endif
                </div>
            </div>

            <!-- Customer Info -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Customer Information</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    @if($fraudLog->user)
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Name</span>
                            <span style="font-weight: 500; color: #303030;">{{ $fraudLog->user->full_name ?? $fraudLog->user->first_name . ' ' . $fraudLog->user->last_name }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Email</span>
                            <span style="font-weight: 500; color: #303030;">{{ $fraudLog->user->email }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Phone</span>
                            <span style="font-weight: 500; color: #303030;">{{ $fraudLog->user->phone ?? '-' }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Member Since</span>
                            <span style="font-weight: 500; color: #303030;">{{ $fraudLog->user->created_at->format('M d, Y') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; align-items: center;">
                            <span style="color: #616161;">IP Address</span>
                            <span style="font-family: monospace; font-size: 12px; background: #f6f6f7; color: #616161; padding: 0.125rem 0.5rem; border-radius: 0.25rem;">{{ $fraudLog->ip_address ?? '-' }}</span>
                        </div>
                    @else
                        <p style="font-size: 13px; color: #616161; margin: 0;">Customer has been deleted</p>
                    @endif
                </div>
            </div>

            <!-- Fraud Indicators -->
            <div class="card" style="overflow: hidden;">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Fraud Indicators</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f6f6f7; border-bottom: 1px solid #e3e3e3;">
                                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 12px; font-weight: 600; color: #616161; text-transform: uppercase; letter-spacing: 0.05em;">Check</th>
                                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 12px; font-weight: 600; color: #616161; text-transform: uppercase; letter-spacing: 0.05em;">Score</th>
                                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 12px; font-weight: 600; color: #616161; text-transform: uppercase; letter-spacing: 0.05em;">Max</th>
                                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 12px; font-weight: 600; color: #616161; text-transform: uppercase; letter-spacing: 0.05em;">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fraudLog->indicators ?? [] as $indicator)
                                <tr style="border-bottom: 1px solid #f6f6f7;">
                                    <td style="padding: 0.75rem 1rem;">
                                        <span style="font-size: 13px; font-weight: 500; color: #303030;">{{ $indicator['name'] ?? '-' }}</span>
                                    </td>
                                    <td style="padding: 0.75rem 1rem; text-align: center;">
                                        @php
                                            $indicatorScore = $indicator['score'] ?? 0;
                                            $indicatorMax = $indicator['max'] ?? 100;
                                            $indicatorPct = $indicatorMax > 0 ? ($indicatorScore / $indicatorMax) * 100 : 0;
                                            if ($indicatorPct >= 75) {
                                                $indicatorStyle = 'color: #d72c0d; font-weight: 700;';
                                            } elseif ($indicatorPct >= 50) {
                                                $indicatorStyle = 'color: #b98900; font-weight: 600;';
                                            } else {
                                                $indicatorStyle = 'color: #303030;';
                                            }
                                        @endphp
                                        <span style="font-size: 13px; {{ $indicatorStyle }}">{{ $indicatorScore }}</span>
                                    </td>
                                    <td style="padding: 0.75rem 1rem; text-align: center; font-size: 13px; color: #616161;">
                                        {{ $indicatorMax }}
                                    </td>
                                    <td style="padding: 0.75rem 1rem; font-size: 13px; color: #616161;">
                                        {{ $indicator['detail'] ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="padding: 2rem 1rem; text-align: center; font-size: 13px; color: #616161;">No fraud indicators recorded</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Customer Fraud History -->
            <div class="card" style="overflow: hidden;">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Customer Fraud History</h2>
                </div>
                @if($userHistory->count() > 0)
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f6f6f7; border-bottom: 1px solid #e3e3e3;">
                                    <th style="padding: 0.75rem 1rem; text-align: left; font-size: 12px; font-weight: 600; color: #616161; text-transform: uppercase; letter-spacing: 0.05em;">Date</th>
                                    <th style="padding: 0.75rem 1rem; text-align: left; font-size: 12px; font-weight: 600; color: #616161; text-transform: uppercase; letter-spacing: 0.05em;">Order</th>
                                    <th style="padding: 0.75rem 1rem; text-align: center; font-size: 12px; font-weight: 600; color: #616161; text-transform: uppercase; letter-spacing: 0.05em;">Risk Score</th>
                                    <th style="padding: 0.75rem 1rem; text-align: center; font-size: 12px; font-weight: 600; color: #616161; text-transform: uppercase; letter-spacing: 0.05em;">Action</th>
                                    <th style="padding: 0.75rem 1rem; text-align: right; font-size: 12px; font-weight: 600; color: #616161; text-transform: uppercase; letter-spacing: 0.05em;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($userHistory as $history)
                                    <tr style="border-bottom: 1px solid #f6f6f7; {{ $history->id === $fraudLog->id ? 'background: #f0f6ff;' : '' }}">
                                        <td style="padding: 0.75rem 1rem; font-size: 13px; color: #616161;">
                                            {{ $history->created_at->format('M d, Y') }}
                                        </td>
                                        <td style="padding: 0.75rem 1rem; font-size: 13px;">
                                            @if($history->order)
                                                <span style="font-weight: 500; color: #303030;">{{ $history->order->order_number }}</span>
                                            @else
                                                <span style="color: #616161;">N/A</span>
                                            @endif
                                        </td>
                                        <td style="padding: 0.75rem 1rem; text-align: center;">
                                            @php
                                                $hScore = $history->risk_score ?? 0;
                                                if ($hScore >= 75) {
                                                    $hScoreStyle = 'background: #ffe0db; color: #b71c00;';
                                                } elseif ($hScore >= 50) {
                                                    $hScoreStyle = 'background: #fff3cd; color: #8a6d00;';
                                                } elseif ($hScore >= 25) {
                                                    $hScoreStyle = 'background: #d4edfc; color: #0064a4;';
                                                } else {
                                                    $hScoreStyle = 'background: #cdfee1; color: #1a7a2e;';
                                                }
                                            @endphp
                                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 700; {{ $hScoreStyle }}">
                                                {{ $hScore }}
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem 1rem; text-align: center;">
                                            @php
                                                $hActionStyle = match($history->action ?? '') {
                                                    'flagged' => 'background: #fff3cd; color: #8a6d00;',
                                                    'blocked' => 'background: #ffe0db; color: #b71c00;',
                                                    'allowed' => 'background: #cdfee1; color: #1a7a2e;',
                                                    default => 'background: #ebebeb; color: #616161;',
                                                };
                                            @endphp
                                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; {{ $hActionStyle }}">
                                                {{ ucfirst($history->action ?? 'Pending') }}
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem 1rem; text-align: right;">
                                            @if($history->id !== $fraudLog->id)
                                                <a href="{{ route('admin.fraud.show', $history) }}" style="color: #005bd3; font-size: 12px; font-weight: 500; text-decoration: none;">View</a>
                                            @else
                                                <span style="font-size: 12px; color: #616161;">Current</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="padding: 2rem; text-align: center; font-size: 13px; color: #616161;">
                        No previous fraud history for this customer.
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Risk Score -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Risk Score</h2>
                </div>
                <div style="padding: 1.5rem; text-align: center;">
                    @php
                        $riskScore = $fraudLog->risk_score ?? 0;
                        if ($riskScore >= 75) {
                            $riskColor = '#d72c0d';
                            $riskBg = '#ffe0db';
                            $riskLabel = 'High Risk';
                        } elseif ($riskScore >= 50) {
                            $riskColor = '#b98900';
                            $riskBg = '#fff3cd';
                            $riskLabel = 'Medium Risk';
                        } elseif ($riskScore >= 25) {
                            $riskColor = '#0064a4';
                            $riskBg = '#d4edfc';
                            $riskLabel = 'Low Risk';
                        } else {
                            $riskColor = '#1a7a2e';
                            $riskBg = '#cdfee1';
                            $riskLabel = 'Minimal Risk';
                        }
                    @endphp
                    <div style="display: inline-flex; align-items: center; justify-content: center; width: 6rem; height: 6rem; border-radius: 50%; background: {{ $riskBg }}; margin-bottom: 0.75rem;">
                        <span style="font-size: 2rem; font-weight: 700; color: {{ $riskColor }};">{{ $riskScore }}</span>
                    </div>
                    <p style="font-size: 13px; font-weight: 600; color: {{ $riskColor }}; margin: 0;">{{ $riskLabel }}</p>
                    <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;">out of 100</p>
                </div>
            </div>

            <!-- Current Status -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Status</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Fraud Type</span>
                        <span style="font-weight: 500; color: #303030;">{{ ucfirst(str_replace('_', ' ', $fraudLog->type ?? '-')) }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; align-items: center;">
                        <span style="color: #616161;">Current Action</span>
                        @php
                            $currentActionStyle = match($fraudLog->action ?? '') {
                                'flagged' => 'background: #fff3cd; color: #8a6d00;',
                                'blocked' => 'background: #ffe0db; color: #b71c00;',
                                'allowed' => 'background: #cdfee1; color: #1a7a2e;',
                                default => 'background: #ebebeb; color: #616161;',
                            };
                        @endphp
                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; {{ $currentActionStyle }}">
                            {{ ucfirst($fraudLog->action ?? 'Pending') }}
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Reviewed</span>
                        <span style="font-weight: 500; color: #303030;">{{ $fraudLog->reviewed_at ? 'Yes' : 'No' }}</span>
                    </div>
                    @if($fraudLog->reviewed_at)
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Reviewed At</span>
                            <span style="font-weight: 500; color: #303030;">{{ $fraudLog->reviewed_at->format('M d, Y h:i A') }}</span>
                        </div>
                    @endif
                    @if($fraudLog->reviewed_by)
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Reviewed By</span>
                            <span style="font-weight: 500; color: #303030;">{{ $fraudLog->reviewer->name ?? 'Admin #' . $fraudLog->reviewed_by }}</span>
                        </div>
                    @endif
                    @if($fraudLog->notes)
                        <div style="padding-top: 0.5rem; border-top: 1px solid #e3e3e3;">
                            <p style="font-size: 13px; color: #616161; margin: 0 0 0.25rem 0;">Notes</p>
                            <p style="font-size: 13px; color: #303030; margin: 0;">{{ $fraudLog->notes }}</p>
                        </div>
                    @endif
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Logged At</span>
                        <span style="font-weight: 500; color: #303030;">{{ $fraudLog->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                </div>
            </div>

            <!-- Review Form -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Review Action</h2>
                </div>
                <form action="{{ route('admin.fraud.review', $fraudLog) }}" method="POST" style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    @csrf
                    @method('PUT')
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Action <span style="color: #d72c0d;">*</span></label>
                        <select name="action" class="form-input" style="width: 100%;" required>
                            <option value="">Select action...</option>
                            <option value="allowed" {{ old('action', $fraudLog->action) === 'allowed' ? 'selected' : '' }}>Allowed</option>
                            <option value="flagged" {{ old('action', $fraudLog->action) === 'flagged' ? 'selected' : '' }}>Flagged</option>
                            <option value="blocked" {{ old('action', $fraudLog->action) === 'blocked' ? 'selected' : '' }}>Blocked</option>
                        </select>
                        @error('action')
                            <p style="margin-top: 0.25rem; font-size: 12px; color: #d72c0d;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Notes</label>
                        <textarea name="notes" rows="4" class="form-input" style="width: 100%;" placeholder="Add review notes...">{{ old('notes', $fraudLog->notes) }}</textarea>
                        @error('notes')
                            <p style="margin-top: 0.25rem; font-size: 12px; color: #d72c0d;">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Submit Review
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>
