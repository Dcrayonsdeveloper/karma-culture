<x-layouts.admin>
    <x-slot name="title">Analytics</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Analytics</h1>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <form action="{{ route('admin.reports.analytics') }}" method="GET">
                    <select name="period" onchange="this.form.submit()" class="form-select" style="font-size: 13px;">
                        <option value="7"  @selected($period == 7)>Last 7 days</option>
                        <option value="30" @selected($period == 30)>Last 30 days</option>
                        <option value="90" @selected($period == 90)>Last 90 days</option>
                    </select>
                </form>
            </div>
        </div>
    </x-slot>

    {{-- Summary Stats --}}
    @php
        $summaryCards = [
            ['label' => 'Unique Visitors', 'value' => number_format($funnel['visitors'])],
            ['label' => 'Product Views',   'value' => number_format($funnel['product_views'])],
            ['label' => 'Add to Cart',     'value' => number_format($funnel['add_to_cart'])],
            ['label' => 'Completed Orders','value' => number_format($funnel['completed'])],
        ];
    @endphp
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        @foreach($summaryCards as $card)
            <div style="background: white; padding: 0.875rem 1rem;">
                <div style="font-size: 12px; color: #616161;">{{ $card['label'] }}</div>
                <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ $card['value'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Conversion Rate Banner --}}
    @if($funnel['visitors'] > 0)
        @php
            $rate = round(($funnel['completed'] / $funnel['visitors']) * 100, 2);
            $cartRate = $funnel['visitors'] > 0 ? round(($funnel['add_to_cart'] / $funnel['visitors']) * 100, 1) : 0;
            $checkoutRate = $funnel['add_to_cart'] > 0 ? round(($funnel['checkout'] / $funnel['add_to_cart']) * 100, 1) : 0;
        @endphp
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
            <div style="background: white; padding: 0.875rem 1rem; border-left: 3px solid #005bd3;">
                <div style="font-size: 12px; color: #616161;">View &rarr; Cart Rate</div>
                <div style="font-size: 1.25rem; font-weight: 600; color: #303030; margin-top: 0.125rem;">{{ $cartRate }}%</div>
                <div style="font-size: 12px; color: #616161; margin-top: 0.25rem;">{{ number_format($funnel['add_to_cart']) }} of {{ number_format($funnel['visitors']) }} visitors</div>
            </div>
            <div style="background: white; padding: 0.875rem 1rem; border-left: 3px solid #b98900;">
                <div style="font-size: 12px; color: #616161;">Cart &rarr; Order Rate</div>
                <div style="font-size: 1.25rem; font-weight: 600; color: #303030; margin-top: 0.125rem;">{{ $checkoutRate }}%</div>
                <div style="font-size: 12px; color: #616161; margin-top: 0.25rem;">{{ number_format($funnel['checkout']) }} of {{ number_format($funnel['add_to_cart']) }} carts</div>
            </div>
            <div style="background: white; padding: 0.875rem 1rem; border-left: 3px solid #1a7a2e;">
                <div style="font-size: 12px; color: #616161;">Overall Conversion</div>
                <div style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e; margin-top: 0.125rem;">{{ $rate }}%</div>
                <div style="font-size: 12px; color: #616161; margin-top: 0.25rem;">{{ number_format($funnel['completed']) }} completed of {{ number_format($funnel['visitors']) }}</div>
            </div>
        </div>
    @endif

    {{-- Traffic Chart --}}
    <div class="card" style="margin-bottom: 1rem;">
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Traffic Overview</h2>
                <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Product views & unique visitors — last {{ $period }} days</p>
            </div>
            @if($trafficData->sum('pageviews') > 0)
                <div style="display: flex; align-items: center; gap: 1rem; font-size: 12px; color: #616161;">
                    <span style="display: flex; align-items: center; gap: 0.375rem;">
                        <span style="display: inline-block; width: 0.75rem; height: 0.75rem; border-radius: 2px; background: rgba(156,0,173,0.4);"></span>
                        Page Views
                    </span>
                    <span style="display: flex; align-items: center; gap: 0.375rem;">
                        <span style="display: inline-block; width: 0.75rem; height: 2px; background: #06b6d4;"></span>
                        Visitors
                    </span>
                </div>
            @endif
        </div>
        <div style="padding: 1rem;">
            @if($trafficData->sum('pageviews') > 0)
                <div style="height: 260px; position: relative;">
                    <canvas id="trafficChart"></canvas>
                </div>
            @else
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1rem; color: #616161;">
                    <svg style="width: 3rem; height: 3rem; margin-bottom: 0.75rem; color: #babfc3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p style="font-size: 13px; font-weight: 500;">No traffic data for this period</p>
                    <p style="font-size: 12px; color: #babfc3; margin-top: 0.25rem;">Product view tracking will appear here once customers start browsing</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Funnel + Sources/Devices --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
        {{-- Conversion Funnel --}}
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Conversion Funnel</h2>
                <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Drop-off at each stage</p>
            </div>
            <div style="padding: 1rem;">
                @php
                    $funnelSteps = [
                        ['label' => 'Unique Visitors',   'value' => $funnel['visitors'],      'color' => '#005bd3'],
                        ['label' => 'Product Views',     'value' => $funnel['product_views'], 'color' => '#2a7de1'],
                        ['label' => 'Add to Cart',       'value' => $funnel['add_to_cart'],   'color' => '#b98900'],
                        ['label' => 'Orders Placed',     'value' => $funnel['checkout'],      'color' => '#0e7090'],
                        ['label' => 'Paid & Completed',  'value' => $funnel['completed'],     'color' => '#1a7a2e'],
                    ];
                    $maxFunnel = max($funnel['visitors'], $funnel['product_views'], 1);
                @endphp

                @foreach($funnelSteps as $index => $step)
                    @php
                        $width   = ($step['value'] / $maxFunnel) * 100;
                        $prev    = $index > 0 ? $funnelSteps[$index - 1]['value'] : $step['value'];
                        $dropoff = $prev > 0 ? round((1 - $step['value'] / $prev) * 100) : 0;
                    @endphp
                    <div style="margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 13px; margin-bottom: 0.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="width: 0.5rem; height: 0.5rem; border-radius: 50%; background: {{ $step['color'] }}; flex-shrink: 0; display: inline-block;"></span>
                                <span style="color: #303030; font-weight: 500;">{{ $step['label'] }}</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="font-weight: 600; color: #303030;">{{ number_format($step['value']) }}</span>
                                @if($index > 0 && $dropoff > 0)
                                    <span style="font-size: 11px; font-weight: 500; color: #d72c0d; background: #fef0ee; padding: 0.125rem 0.375rem; border-radius: 9999px;">-{{ $dropoff }}%</span>
                                @elseif($index > 0 && $dropoff === 0)
                                    <span style="font-size: 11px; font-weight: 500; color: #1a7a2e; background: #eefbe9; padding: 0.125rem 0.375rem; border-radius: 9999px;">0%</span>
                                @endif
                            </div>
                        </div>
                        <div style="background: #f0f0f0; border-radius: 9999px; height: 0.625rem; overflow: hidden;">
                            <div style="background: {{ $step['color'] }}; height: 100%; border-radius: 9999px; width: {{ max($width, 1) }}%; transition: all 0.7s;"></div>
                        </div>
                    </div>
                @endforeach

                @if($funnel['visitors'] == 0)
                    <p style="text-align: center; font-size: 13px; color: #616161; padding: 1rem 0;">No funnel data for this period</p>
                @endif
            </div>
        </div>

        {{-- Right column: Traffic Sources + Device Breakdown --}}
        <div>
            {{-- Traffic Sources --}}
            <div class="card" style="margin-bottom: 1rem;">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Traffic Sources</h2>
                    <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">By referrer origin</p>
                </div>
                <div>
                    @php
                        $sourceIcons = [
                            'Organic Search' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
                            'Direct'         => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
                            'Social Media'   => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
                            'Email'          => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                            'Referral'       => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064',
                        ];
                        $hasSourceData = $sources->where('visitors', '>', 0)->count() > 0;
                    @endphp
                    @if($hasSourceData)
                        @foreach($sources->filter(fn($s) => $s['visitors'] > 0) as $source)
                            @php $si = $sourceIcons[$source['source']] ?? $sourceIcons['Referral']; @endphp
                            <div style="padding: 0.625rem 1rem; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 0.75rem;">
                                <div style="width: 2rem; height: 2rem; border-radius: 0.5rem; background: #f6f6f7; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg style="width: 0.875rem; height: 0.875rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $si }}"/>
                                    </svg>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.375rem;">
                                        <span style="font-size: 13px; font-weight: 500; color: #303030;">{{ $source['source'] }}</span>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="font-size: 13px; font-weight: 600; color: #303030;">{{ number_format($source['visitors']) }}</span>
                                            <span style="font-size: 12px; color: #616161; width: 2rem; text-align: right;">{{ $source['percentage'] }}%</span>
                                        </div>
                                    </div>
                                    <div style="background: #f0f0f0; border-radius: 9999px; height: 0.375rem; overflow: hidden;">
                                        <div style="background: #005bd3; height: 100%; border-radius: 9999px; width: {{ $source['percentage'] }}%;"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div style="padding: 2rem 1rem; text-align: center;">
                            <svg style="width: 2rem; height: 2rem; margin: 0 auto 0.5rem auto; display: block; color: #babfc3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945"/>
                            </svg>
                            <p style="font-size: 13px; color: #616161;">No referrer data available</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Device Breakdown --}}
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Device Breakdown</h2>
                    <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Orders by device type</p>
                </div>
                <div style="padding: 1rem;">
                    @if($devices['mobile'] + $devices['desktop'] + $devices['tablet'] > 0)
                        {{-- Stacked bar --}}
                        <div style="display: flex; height: 0.75rem; border-radius: 9999px; overflow: hidden; margin-bottom: 1.25rem; gap: 2px;">
                            @if($devices['mobile'] > 0)
                                <div style="background: #005bd3; border-radius: 9999px 0 0 9999px; width: {{ $devices['mobile'] }}%;" title="Mobile {{ $devices['mobile'] }}%"></div>
                            @endif
                            @if($devices['desktop'] > 0)
                                <div style="background: #0e7090; width: {{ $devices['desktop'] }}%; {{ $devices['mobile'] == 0 ? 'border-radius: 9999px 0 0 9999px;' : '' }} {{ $devices['tablet'] == 0 ? 'border-radius: 0 9999px 9999px 0;' : '' }}" title="Desktop {{ $devices['desktop'] }}%"></div>
                            @endif
                            @if($devices['tablet'] > 0)
                                <div style="background: #b98900; border-radius: 0 9999px 9999px 0; width: {{ $devices['tablet'] }}%;" title="Tablet {{ $devices['tablet'] }}%"></div>
                            @endif
                        </div>

                        @php
                            $deviceItems = [
                                ['label' => 'Mobile',  'pct' => $devices['mobile'],  'color' => '#005bd3'],
                                ['label' => 'Desktop', 'pct' => $devices['desktop'], 'color' => '#0e7090'],
                                ['label' => 'Tablet',  'pct' => $devices['tablet'],  'color' => '#b98900'],
                            ];
                        @endphp
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;">
                            @foreach($deviceItems as $d)
                                <div style="background: #f6f6f7; border-radius: 0.75rem; padding: 0.75rem; text-align: center;">
                                    <div style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $d['pct'] }}%</div>
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 0.25rem; margin-top: 0.25rem;">
                                        <span style="width: 0.5rem; height: 0.5rem; border-radius: 50%; background: {{ $d['color'] }}; flex-shrink: 0; display: inline-block;"></span>
                                        <span style="font-size: 12px; color: #616161;">{{ $d['label'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="padding: 2rem 0; text-align: center;">
                            <svg style="width: 2rem; height: 2rem; margin: 0 auto 0.5rem auto; display: block; color: #babfc3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <p style="font-size: 13px; color: #616161;">No device data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Info note --}}
    <div style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 1rem; background: #f6f6f7; border: 1px solid #e3e3e3; border-radius: 0.75rem; font-size: 12px; color: #616161;">
        <svg style="width: 1rem; height: 1rem; flex-shrink: 0; margin-top: 0.125rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Analytics data is collected from product views, cart activity, and order records. Traffic sources are derived from HTTP referrer headers. Device breakdown is based on user-agent strings from orders.
    </div>

    @if($trafficData->sum('pageviews') > 0)
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const fontFamily = "'Manrope', 'Inter', sans-serif";
                const ctx = document.getElementById('trafficChart');

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: @json($trafficData->pluck('date')),
                        datasets: [
                            {
                                label: 'Page Views',
                                data: @json($trafficData->pluck('pageviews')),
                                backgroundColor: 'rgba(156, 0, 173, 0.18)',
                                hoverBackgroundColor: 'rgba(156, 0, 173, 0.35)',
                                borderColor: 'rgba(156, 0, 173, 0.6)',
                                borderWidth: 1,
                                borderRadius: 5,
                                borderSkipped: false,
                                yAxisID: 'y',
                                order: 2,
                            },
                            {
                                label: 'Unique Visitors',
                                data: @json($trafficData->pluck('visitors')),
                                type: 'line',
                                borderColor: '#06b6d4',
                                backgroundColor: 'rgba(6, 182, 212, 0.06)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#06b6d4',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: {{ $period <= 14 ? 4 : 0 }},
                                pointHoverRadius: 5,
                                yAxisID: 'y',
                                order: 1,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#1a1a1a',
                                titleColor: '#fff',
                                bodyColor: '#ccc',
                                padding: 10,
                                cornerRadius: 8,
                                titleFont: { size: 11, family: fontFamily },
                                bodyFont: { size: 11, family: fontFamily },
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: {
                                    font: { size: 10, family: fontFamily },
                                    color: '#a0a0a0',
                                    maxRotation: 40,
                                    maxTicksLimit: {{ $period <= 14 ? $period : 15 }}
                                }
                            },
                            y: {
                                grid: { color: '#f3f3f3' },
                                border: { display: false, dash: [4, 4] },
                                ticks: {
                                    font: { size: 10, family: fontFamily },
                                    color: '#a0a0a0',
                                    maxTicksLimit: 6
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });
            });
        </script>
        @endpush
    @endif
</x-layouts.admin>
