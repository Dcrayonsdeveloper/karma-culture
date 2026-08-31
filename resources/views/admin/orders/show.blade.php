<x-layouts.admin>
    <x-slot name="title">Order {{ $order->order_number }}</x-slot>

    <x-slot name="header">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <a href="{{ route('admin.orders.index') }}" style="display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: 0.5rem; color: #616161; text-decoration: none;" class="btn-icon">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ $order->order_number }}</h1>
                    @php
                        $statusBadge = match($order->status) {
                            'delivered', 'completed' => 'badge-success',
                            'confirmed' => 'badge-warning',
                            'processing', 'packed' => 'badge-info',
                            'shipped', 'out_for_delivery' => 'badge-info',
                            'cancelled', 'returned' => 'badge-error',
                            default => 'badge-neutral',
                        };
                        $payBadge = match($order->payment_status) {
                            'paid' => 'badge-success',
                            'pending' => 'badge-warning',
                            'failed' => 'badge-error',
                            default => 'badge-neutral',
                        };
                    @endphp
                    <span class="badge {{ $statusBadge }}">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                    <span class="badge {{ $payBadge }}">{{ ucfirst($order->payment_status) }}</span>
                </div>
                <p style="font-size: 13px; color: #616161; margin-top: 2px;">{{ $order->created_at->format('M d, Y \a\t g:i A') }}</p>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <a href="{{ route('admin.orders.invoice', $order) }}" class="btn btn-secondary" target="_blank" style="font-size: 13px;">Print invoice</a>
                <a href="{{ route('admin.orders.packing-slip', $order) }}" class="btn btn-secondary" target="_blank" style="font-size: 13px;">Packing slip</a>
            </div>
        </div>
    </x-slot>

    {{-- Order Tracking Timeline --}}
    @if(!in_array($order->status, ['pending', 'cancelled', 'returned']))
        <div class="card" style="margin-bottom: 1rem;">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Order tracking</h2>
                @if($latestShipment && $latestShipment->tracking_number)
                    <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 13px;">
                        <span style="color: #616161;">Tracking:</span>
                        <span style="font-family: monospace; font-weight: 600; color: #005bd3;">{{ $latestShipment->tracking_number }}</span>
                        @if($latestShipment->carrier)
                            <span class="badge badge-neutral">{{ $latestShipment->carrier }}</span>
                        @endif
                    </div>
                @endif
            </div>
            <div style="padding: 1.25rem 1rem;">
                <div style="position: relative; display: flex; align-items: flex-start; justify-content: space-between;">
                    @foreach($trackingSteps as $index => $step)
                        <div style="flex: 1; {{ $index < count($trackingSteps) - 1 ? 'position: relative;' : '' }}">
                            <div style="display: flex; flex-direction: column; align-items: center;">
                                <div style="width: 2rem; height: 2rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; z-index: 1; position: relative;
                                    {{ $step['completed'] ? 'background: #1a7a2e; color: white;' : ($step['current'] ? 'background: #005bd3; color: white; box-shadow: 0 0 0 3px #d4edfc;' : 'background: #e3e3e3; color: #999;') }}">
                                    @if($step['completed'] && !$step['current'])
                                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        <svg style="width: 0.875rem; height: 0.875rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            @if($step['icon'] === 'clipboard-check')
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                            @elseif($step['icon'] === 'cube')
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            @elseif($step['icon'] === 'truck')
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                            @elseif($step['icon'] === 'map-pin')
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            @elseif($step['icon'] === 'check-circle')
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            @endif
                                        </svg>
                                    @endif
                                </div>
                                <p style="margin-top: 0.375rem; font-size: 12px; font-weight: 500; text-align: center; color: {{ $step['completed'] || $step['current'] ? '#303030' : '#999' }};">
                                    {{ $step['label'] }}
                                </p>
                                @if($step['timestamp'])
                                    <p style="font-size: 11px; color: #999; text-align: center; margin-top: 1px;">
                                        {{ $step['timestamp']->format('M d, h:i A') }}
                                    </p>
                                @endif
                            </div>
                            @if($index < count($trackingSteps) - 1)
                                <div style="position: absolute; top: 1rem; left: 50%; width: 100%; height: 2px; {{ $trackingSteps[$index + 1]['completed'] || $trackingSteps[$index + 1]['current'] ? 'background: #1a7a2e;' : 'background: #e3e3e3;' }}"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;" class="lg:!grid-cols-[1fr_340px]">
        {{-- Main Content --}}
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            {{-- Order Items --}}
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Items</h2>
                </div>
                @foreach($order->items as $item)
                    <div style="padding: 0.75rem 1rem; display: flex; gap: 0.75rem; border-bottom: 1px solid #f1f1f1;">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; background: #f7f7f7; border: 1px solid #e3e3e3; overflow: hidden; flex-shrink: 0;">
                            @if($item->product->primary_image_url ?? null)
                                <img src="{{ $item->product->primary_image_url }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                            @endif
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <p style="font-size: 13px; font-weight: 500; color: #005bd3;">{{ $item->product_name }}</p>
                            @if($item->variant_name)
                                <p style="font-size: 12px; color: #616161;">{{ $item->variant_name }}</p>
                            @endif
                            @if($item->size)
                                <p style="font-size: 12px; color: #616161;">Size: <strong>{{ $item->size }}</strong></p>
                            @endif
                            @if($item->colour)
                                <p style="font-size: 12px; color: #616161;">Colour: <strong>{{ $item->colour }}</strong></p>
                            @endif
                            <p style="font-size: 12px; color: #999; font-family: monospace;">SKU: {{ $item->sku }}</p>
                        </div>
                        <div style="text-align: right; flex-shrink: 0;">
                            <p style="font-size: 13px; color: #616161;">{{ $order->currency }} {{ number_format($item->price, 2) }} &times; {{ $item->quantity }}</p>
                            <p style="font-size: 13px; font-weight: 600; color: #303030;">{{ $order->currency }} {{ number_format($item->total, 2) }}</p>
                        </div>
                    </div>
                @endforeach
                {{-- Totals --}}
                <div style="padding: 0.75rem 1rem; background: #fafafa;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: #616161; padding: 0.25rem 0;">
                        <span>Subtotal</span>
                        <span>{{ $order->currency }} {{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    @if($order->discount > 0)
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #1a7a2e; padding: 0.25rem 0;">
                            <span>Discount</span>
                            <span>-{{ $order->currency }} {{ number_format($order->discount, 2) }}</span>
                        </div>
                    @endif
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: #616161; padding: 0.25rem 0;">
                        <span>Shipping</span>
                        <span>{{ $order->currency }} {{ number_format($order->shipping_cost, 2) }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: #616161; padding: 0.25rem 0;">
                        <span>Tax</span>
                        <span>{{ $order->currency }} {{ number_format($order->tax, 2) }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: 600; color: #303030; padding: 0.5rem 0 0.25rem; border-top: 1px solid #e3e3e3; margin-top: 0.25rem;">
                        <span>Total</span>
                        <span>{{ $order->currency }} {{ number_format($order->total, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Shipping Address --}}
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Shipping address</h2>
                </div>
                <div style="padding: 1rem;">
                    @php $shipping = $order->shipping_address_snapshot; @endphp
                    @if($shipping)
                        <div style="font-size: 13px; color: #303030; line-height: 1.6;">
                            <p style="font-weight: 500;">{{ $shipping['name'] ?? ($shipping['first_name'] ?? '') . ' ' . ($shipping['last_name'] ?? '') }}</p>
                            @if(!empty($shipping['phone'])) <p style="color: #616161;">{{ $shipping['phone'] }}</p> @endif
                            @if(!empty($shipping['address'])) <p style="color: #616161;">{{ $shipping['address'] }}</p> @endif
                            @if(!empty($shipping['address_line_1'])) <p style="color: #616161;">{{ $shipping['address_line_1'] }}</p> @endif
                            <p style="color: #616161;">{{ $shipping['city'] ?? '' }}{{ !empty($shipping['state']) ? ', ' . $shipping['state'] : '' }} {{ $shipping['postal_code'] ?? $shipping['zip'] ?? '' }}</p>
                        </div>
                    @elseif($order->user)
                        <p style="font-size: 13px; color: #616161;">{{ $order->user->full_name }} &middot; {{ $order->user->email }}</p>
                    @endif
                </div>
            </div>

            {{-- Order Notes --}}
            @if($order->notes)
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Notes</h2>
                    </div>
                    <div style="padding: 1rem;">
                        <p style="font-size: 13px; color: #616161;">{{ $order->notes }}</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            {{-- Update Status --}}
            {{-- Start on the first legal move, not the current status: the current
                 status is deliberately not one of the options. --}}
            <div class="card" x-data="{ status: '{{ $order->allowedNextStatuses()[0] ?? '' }}' }">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Update status</h2>
                </div>
                <div style="padding: 1rem;">
                    <form action="{{ route('admin.orders.status', $order) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div style="margin-bottom: 0.75rem;">
                            <label style="font-size: 13px; font-weight: 500; color: #303030; display: block; margin-bottom: 0.25rem;">Status</label>
                            {{-- Only offer the moves this order can actually make, so an
                                 admin never picks a status the server will reject. --}}
                            @php $kkNext = $order->allowedNextStatuses(); @endphp
                            <select name="status" x-model="status" @disabled(empty($kkNext)) style="width: 100%; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; padding: 0.375rem 0.5rem;">
                                @forelse($kkNext as $kkStatus)
                                    <option value="{{ $kkStatus }}">{{ ucwords(str_replace('_', ' ', $kkStatus)) }}</option>
                                @empty
                                    <option value="">No further changes possible</option>
                                @endforelse
                            </select>
                        </div>

                        @if($shiprocketEnabled && !empty($order->metadata['shiprocket_order_id']))
                            {{-- Shiprocket handles carrier/tracking automatically --}}
                            <div x-show="status === 'shipped'" x-transition x-cloak style="margin-bottom: 0.75rem;">
                                <div style="padding: 0.5rem 0.75rem; background: #f0f4ff; border-radius: 0.5rem; border: 1px solid #d4e0ff; font-size: 12px; color: #303030;">
                                    Carrier & tracking managed by Shiprocket automatically.
                                    @if(!empty($order->metadata['shiprocket_awb']))
                                        <br>AWB: <strong>{{ $order->metadata['shiprocket_awb'] }}</strong>
                                    @endif
                                </div>
                                <input type="hidden" name="carrier" value="{{ $order->metadata['shiprocket_courier'] ?? 'Shiprocket' }}">
                                <input type="hidden" name="tracking_number" value="{{ $order->metadata['shiprocket_awb'] ?? '' }}">
                            </div>
                        @else
                            <div x-show="status === 'shipped'" x-transition x-cloak style="margin-bottom: 0.75rem;">
                                <div style="margin-bottom: 0.5rem;">
                                    <label style="font-size: 13px; font-weight: 500; color: #303030; display: block; margin-bottom: 0.25rem;">Carrier</label>
                                    <select name="carrier" style="width: 100%; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; padding: 0.375rem 0.5rem;">
                                        <option value="">Select carrier</option>
                                        <option value="BlueDart">BlueDart</option>
                                        <option value="Delhivery">Delhivery</option>
                                        <option value="DTDC">DTDC</option>
                                        <option value="Ecom Express">Ecom Express</option>
                                        <option value="India Post">India Post</option>
                                        <option value="FedEx">FedEx</option>
                                        <option value="DHL">DHL</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size: 13px; font-weight: 500; color: #303030; display: block; margin-bottom: 0.25rem;">Tracking number</label>
                                    <input type="text" name="tracking_number" style="width: 100%; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; padding: 0.375rem 0.5rem;" placeholder="Enter tracking number">
                                </div>
                            </div>
                        @endif

                        <div style="margin-bottom: 0.75rem;">
                            <label style="font-size: 13px; font-weight: 500; color: #303030; display: block; margin-bottom: 0.25rem;">Note (optional)</label>
                            <textarea name="comment" rows="2" style="width: 100%; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; padding: 0.375rem 0.5rem; resize: vertical;" placeholder="Add a note..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" @disabled(empty($kkNext)) style="width: 100%; font-size: 13px;">Update status</button>
                    </form>
                </div>
            </div>

            {{-- Shiprocket --}}
            @if($shiprocketEnabled)
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.375rem;">
                            <svg style="width: 1rem; height: 1rem; color: #7C3AED;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125v-3.659a3 3 0 00-.879-2.121l-2.121-2.122a3 3 0 00-2.121-.879H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.988-1.111a48.662 48.662 0 00-3.478-.404c-.668-.049-1.284.366-1.496.994l-.297.882"/></svg>
                            <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Shiprocket</h2>
                        </div>
                        @if(!empty($order->metadata['shiprocket_order_id']))
                            <span class="badge badge-success">Synced</span>
                        @else
                            <span class="badge badge-neutral">Not pushed</span>
                        @endif
                    </div>
                    <div style="padding: 1rem;">
                        @if(!empty($order->metadata['shiprocket_order_id']))
                            <div style="display: flex; flex-direction: column; gap: 0.375rem; font-size: 13px; margin-bottom: 0.75rem;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #616161;">Order ID</span>
                                    <span style="color: #303030; font-family: monospace;">{{ $order->metadata['shiprocket_order_id'] }}</span>
                                </div>
                                @if(!empty($order->metadata['shiprocket_shipment_id']))
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #616161;">Shipment ID</span>
                                    <span style="color: #303030; font-family: monospace;">{{ $order->metadata['shiprocket_shipment_id'] }}</span>
                                </div>
                                @endif
                                @if(!empty($order->metadata['shiprocket_awb']))
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #616161;">AWB</span>
                                    <span style="color: #005bd3; font-family: monospace; font-weight: 600;">{{ $order->metadata['shiprocket_awb'] }}</span>
                                </div>
                                @endif
                                @if(!empty($order->metadata['shiprocket_courier']))
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #616161;">Courier</span>
                                    <span style="color: #303030;">{{ $order->metadata['shiprocket_courier'] }}</span>
                                </div>
                                @endif
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                @if(!empty($order->metadata['shiprocket_label_url']))
                                    <a href="{{ $order->metadata['shiprocket_label_url'] }}" target="_blank" class="btn btn-secondary" style="flex: 1; font-size: 12px; text-align: center;">
                                        <svg style="width: 0.875rem; height: 0.875rem; display: inline; vertical-align: -2px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                        Label
                                    </a>
                                @endif
                                <form action="{{ route('admin.orders.shiprocket.sync', $order) }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary" style="width: 100%; font-size: 12px;">Sync tracking</button>
                                </form>
                                @if(!in_array($order->status, ['delivered', 'cancelled', 'returned']))
                                    <form action="{{ route('admin.orders.shiprocket.cancel', $order) }}" method="POST" style="flex: 1;" onsubmit="return confirm('Cancel this shipment on Shiprocket?')">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary" style="width: 100%; font-size: 12px; color: #d72c0d;">Cancel</button>
                                    </form>
                                @endif
                            </div>
                        @else
                            @if(!in_array($order->status, ['cancelled', 'returned', 'pending']))
                                <form action="{{ route('admin.orders.shiprocket.push', $order) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 13px;">
                                        Push to Shiprocket
                                    </button>
                                </form>
                                <p style="font-size: 11px; color: #616161; margin-top: 0.375rem; text-align: center;">Creates order, assigns AWB & requests pickup</p>
                            @else
                                <p style="font-size: 13px; color: #616161; text-align: center;">Order cannot be pushed in current status.</p>
                            @endif
                        @endif
                    </div>
                </div>
            @endif

            {{-- Delivery Partner --}}
            @if(in_array($order->status, ['packed', 'shipped', 'out_for_delivery']))
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Delivery partner</h2>
                        @if($order->deliveryPartner)
                            <span class="badge badge-success">Assigned</span>
                        @endif
                    </div>
                    <div style="padding: 1rem;">
                        @if($order->deliveryPartner)
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; padding: 0.5rem; background: #f7f7f7; border-radius: 0.5rem;">
                                <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #e3e3e3; display: flex; align-items: center; justify-content: center;">
                                    <span style="font-size: 11px; font-weight: 600; color: #616161;">{{ strtoupper(substr($order->deliveryPartner->user->first_name, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <p style="font-size: 13px; font-weight: 500; color: #303030;">{{ $order->deliveryPartner->user->full_name }}</p>
                                    <p style="font-size: 12px; color: #616161;">{{ $order->deliveryPartner->partner_id }}</p>
                                </div>
                            </div>
                        @endif
                        <form action="{{ route('admin.orders.assign-partner', $order) }}" method="POST">
                            @csrf
                            <select name="delivery_partner_id" style="width: 100%; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; padding: 0.375rem 0.5rem; margin-bottom: 0.5rem;">
                                <option value="">Select partner</option>
                                @foreach($activePartners as $partner)
                                    <option value="{{ $partner->id }}" @selected($order->delivery_partner_id == $partner->id)>
                                        {{ $partner->user->full_name }} ({{ $partner->partner_id }})
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-secondary" style="width: 100%; font-size: 13px;">
                                {{ $order->delivery_partner_id ? 'Update partner' : 'Assign partner' }}
                            </button>
                        </form>
                    </div>
                </div>
            @elseif($order->deliveryPartner)
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Delivery partner</h2>
                    </div>
                    <div style="padding: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #e3e3e3; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 11px; font-weight: 600; color: #616161;">{{ strtoupper(substr($order->deliveryPartner->user->first_name, 0, 1)) }}</span>
                        </div>
                        <div>
                            <p style="font-size: 13px; font-weight: 500; color: #303030;">{{ $order->deliveryPartner->user->full_name }}</p>
                            <p style="font-size: 12px; color: #616161;">{{ $order->deliveryPartner->partner_id }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Customer --}}
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Customer</h2>
                </div>
                <div style="padding: 1rem;">
                    @if($order->user)
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                            <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #e3e3e3; display: flex; align-items: center; justify-content: center;">
                                <span style="font-size: 11px; font-weight: 600; color: #616161;">{{ strtoupper(substr($order->user->first_name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <p style="font-size: 13px; font-weight: 500; color: #005bd3;">{{ $order->user->full_name }}</p>
                                <p style="font-size: 12px; color: #616161;">{{ $order->user->email }}</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.customers.show', $order->user) }}" class="btn btn-secondary" style="width: 100%; font-size: 13px; text-align: center; display: block;">View customer</a>
                    @else
                        <p style="font-size: 13px; color: #616161;">Guest checkout</p>
                    @endif
                </div>
            </div>

            {{-- Order Info --}}
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Order details</h2>
                </div>
                <div style="padding: 1rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Created</span>
                            <span style="color: #303030;">{{ $order->created_at->format('M d, Y') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Payment</span>
                            <span class="badge {{ $payBadge }}">{{ ucfirst($order->payment_status) }}</span>
                        </div>
                        @if($order->payment_collected)
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #616161;">Collected</span>
                                <span class="badge badge-success">Yes</span>
                            </div>
                        @endif
                        @if($latestShipment)
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #616161;">Carrier</span>
                                <span style="color: #303030;">{{ $latestShipment->carrier }}</span>
                            </div>
                        @endif
                        @if($order->shipped_at)
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #616161;">Shipped</span>
                                <span style="color: #303030;">{{ $order->shipped_at->format('M d, Y') }}</span>
                            </div>
                        @endif
                        @if($order->delivered_at)
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #616161;">Delivered</span>
                                <span style="color: #303030;">{{ $order->delivered_at->format('M d, Y') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
                {{-- Expected Delivery --}}
                <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;" x-data="{ editing: false }">
                    <div x-show="!editing" style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <p style="font-size: 12px; color: #616161;">Expected delivery</p>
                            @if($order->expected_delivery_date)
                                <p style="font-size: 13px; font-weight: 500; color: #1a7a2e; margin-top: 1px;">
                                    {{ $order->expected_delivery_date->format('D, M d, Y') }}
                                </p>
                            @else
                                <p style="font-size: 13px; color: #999; margin-top: 1px;">Not set</p>
                            @endif
                        </div>
                        @if(!in_array($order->status, ['delivered', 'cancelled', 'returned']))
                            <button @click="editing = true" style="font-size: 13px; color: #005bd3; font-weight: 500; background: none; border: none; cursor: pointer;">
                                {{ $order->expected_delivery_date ? 'Change' : 'Set' }}
                            </button>
                        @endif
                    </div>
                    @if(!in_array($order->status, ['delivered', 'cancelled', 'returned']))
                        <form x-show="editing" x-cloak action="{{ route('admin.orders.expected-delivery', $order) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="date" name="expected_delivery_date"
                                   value="{{ $order->expected_delivery_date?->format('Y-m-d') }}"
                                   min="{{ today()->format('Y-m-d') }}"
                                   style="width: 100%; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; padding: 0.375rem 0.5rem; margin-bottom: 0.5rem;">
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="submit" class="btn btn-primary btn-sm" style="flex: 1;">Save</button>
                                <button type="button" @click="editing = false" class="btn btn-secondary btn-sm">Cancel</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Activity Log --}}
            @if($order->statusHistory->count())
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Timeline</h2>
                    </div>
                    <div style="padding: 1rem;">
                        <div style="position: relative;">
                            <div style="position: absolute; left: 0.5rem; top: 0; bottom: 0; width: 1px; background: #e3e3e3;"></div>
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                @foreach($order->statusHistory->sortByDesc('created_at') as $history)
                                    <div style="display: flex; gap: 0.5rem; font-size: 13px; position: relative;">
                                        <div style="width: 1rem; height: 1rem; border-radius: 50%; z-index: 1; flex-shrink: 0;
                                            {{ $history->status === 'delivered' ? 'background: #cdfee1;' : (in_array($history->status, ['cancelled', 'returned']) ? 'background: #ffe0db;' : 'background: #d4edfc;') }}
                                            display: flex; align-items: center; justify-content: center;">
                                            <div style="width: 0.375rem; height: 0.375rem; border-radius: 50%;
                                                {{ $history->status === 'delivered' ? 'background: #1a7a2e;' : (in_array($history->status, ['cancelled', 'returned']) ? 'background: #d72c0d;' : 'background: #005bd3;') }}"></div>
                                        </div>
                                        <div style="flex: 1;">
                                            <p style="font-weight: 500; color: #303030;">{{ ucfirst(str_replace('_', ' ', $history->status)) }}</p>
                                            @if($history->comment)
                                                <p style="color: #616161;">{{ $history->comment }}</p>
                                            @endif
                                            <p style="font-size: 12px; color: #999; margin-top: 1px;">{{ $history->created_at->format('M d \a\t g:i A') }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
