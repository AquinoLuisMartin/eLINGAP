@php
    $summary = $this->summary;
    $chart = $this->distribution;
    $buckets = $chart['buckets'];
    $count = count($buckets);
    $actualPoints = [];
    $plannedPoints = [];
    foreach ($buckets as $index => $bucket) {
        $x = 5 + $index * 90 / max(1, $count - 1);
        $actualPoints[] = $x.','.(95 - $bucket['released'] / $chart['scale'] * 90);
        $plannedPoints[] = $x.','.(95 - $bucket['planned'] / $chart['scale'] * 90);
    }
@endphp
<section class="welcome-row">
    <div>
        <p class="section-kicker">{{ now()->format('l, F j, Y') }}</p>
        <h2>Welcome, {{ $this->currentUser->first_name }}</h2>
        <p class="section-subtitle">Registry, benefits, and communications from your database.</p>
    </div>
</section>
<section class="metric-grid">
    <div class="metric-card">
        <div class="metric-icon blue"><x-admin.icon name="users" size="19" /></div>
        <div class="metric-label">Registered Seniors</div>
        <div class="metric-value">{{ number_format($summary['seniors']) }}</div>
        <div class="metric-bottom">{{ number_format($summary['verified']) }} verified records</div>
    </div>
    <div class="metric-card">
        <div class="metric-icon mint"><x-admin.icon name="heart-pulse" size="19" /></div>
        <div class="metric-label">Active Beneficiary Enrollments</div>
        <div class="metric-value">{{ number_format($summary['beneficiaries']) }}</div>
        <div class="metric-bottom">Active enrollments across all programs</div>
    </div>
    <div class="metric-card">
        <div class="metric-icon violet"><x-admin.icon name="message-square" size="19" /></div>
        <div class="metric-label">SMS Sending Success</div>
        <div class="metric-value">{{ $summary['sms_rate'] === null ? 'No attempts' : number_format($summary['sms_rate'], 1).'%' }}</div>
        <div class="metric-bottom">{{ number_format($summary['sms_sent']) }} sent / {{ number_format($summary['sms_attempted']) }} attempted this month</div>
    </div>
    <div class="metric-card">
        <div class="metric-icon amber"><x-admin.icon name="clipboard-list" size="19" /></div>
        <div class="metric-label">Pending Verifications</div>
        <div class="metric-value">{{ number_format($summary['pending']) }}</div>
        <div class="metric-bottom">Senior records awaiting review</div>
    </div>
</section>
<section class="dashboard-grid">
    <div class="panel chart-panel" wire:loading.class="data-refreshing" wire:target="period,chartType">
        <div class="panel-heading">
            <div><h3>Released vs. Scheduled Payouts</h3><p>{{ now()->year }} schedule year / Amounts in Philippine pesos</p></div>
            <div class="segmented" aria-label="Chart period">
                @foreach (['Monthly', 'Quarterly'] as $option)
                    <button wire:key="period-{{ $option }}" type="button" class="{{ $period === $option ? 'selected' : '' }}" wire:click="$set('period', '{{ $option }}')" aria-pressed="{{ $period === $option ? 'true' : 'false' }}">{{ $option }}</button>
                @endforeach
            </div>
        </div>
        <div class="chart-summary">
            <div><small>Released</small><strong>PHP {{ number_format($chart['released'], 2) }}</strong></div>
            <div><small>Scheduled</small><strong>PHP {{ number_format($chart['planned'], 2) }}</strong></div>
            <div><small>Completion</small><strong>{{ $chart['completion'] === null ? 'No payouts' : number_format($chart['completion'], 1).'%' }}</strong></div>
        </div>
        <div class="chart-toolbar">
            <span><i class="legend-dot actual"></i> Released <i class="legend-dot target"></i> Scheduled</span>
            <div class="chart-switch">
                @foreach (['Area', 'Bar'] as $option)
                    <button wire:key="chart-type-{{ $option }}" type="button" class="{{ $chartType === $option ? 'selected' : '' }}" wire:click="$set('chartType', '{{ $option }}')" aria-pressed="{{ $chartType === $option ? 'true' : 'false' }}">{{ $option }}</button>
                @endforeach
            </div>
        </div>
        <p wire:loading wire:target="period,chartType" role="status">Updating chart...</p>
        @if ($chart['planned'] > 0)
            <div class="payout-chart-scroll">
                <div class="payout-chart">
                    <div class="payout-axis">
                        @foreach ([1, .75, .5, .25, 0] as $fraction)
                            <span wire:key="axis-{{ $fraction }}">{{ number_format($chart['scale'] * $fraction) }}</span>
                        @endforeach
                    </div>
                    <div class="payout-plot">
                        <div class="payout-grid" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
                        @if ($chartType === 'Area')
                            <svg viewBox="0 0 100 100" preserveAspectRatio="none" role="img" aria-label="Released and scheduled payout amounts; exact values are in the table below">
                                <polygon class="area-fill" points="5,95 {{ implode(' ', $actualPoints) }} 95,95" />
                                <polyline class="target-line" points="{{ implode(' ', $plannedPoints) }}" />
                                <polyline class="actual-line" points="{{ implode(' ', $actualPoints) }}" />
                                @foreach ($buckets as $index => $bucket)
                                    <circle wire:key="point-{{ $period }}-{{ $index }}" class="actual-point" cx="{{ 5 + $index * 90 / max(1, $count - 1) }}" cy="{{ 95 - $bucket['released'] / $chart['scale'] * 90 }}" r="1.1"><title>{{ $bucket['label'] }}: PHP {{ number_format($bucket['released'], 2) }} released; PHP {{ number_format($bucket['planned'], 2) }} scheduled</title></circle>
                                @endforeach
                            </svg>
                        @else
                            <div class="payout-bars" role="img" aria-label="Released and scheduled payout amounts; exact values are in the table below">
                                @foreach ($buckets as $index => $bucket)
                                    <div class="payout-bar-pair" wire:key="bar-{{ $period }}-{{ $index }}" title="{{ $bucket['label'] }}: PHP {{ number_format($bucket['released'], 2) }} released; PHP {{ number_format($bucket['planned'], 2) }} scheduled">
                                        <i style="height: {{ $bucket['released'] / $chart['scale'] * 100 }}%"></i><b style="height: {{ $bucket['planned'] / $chart['scale'] * 100 }}%"></b>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="payout-labels">
                        @foreach ($buckets as $index => $bucket)
                            <span wire:key="label-{{ $period }}-{{ $index }}">{{ $bucket['label'] }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            <details class="chart-data-table"><summary>View exact amounts</summary><div class="table-scroll"><table>
                <caption class="sr-only">Payout amounts by schedule period, {{ now()->year }}</caption>
                <thead><tr><th scope="col">Period</th><th scope="col">Released (PHP)</th><th scope="col">Scheduled (PHP)</th></tr></thead>
                <tbody>@foreach ($buckets as $index => $bucket)
                    <tr wire:key="amount-{{ $period }}-{{ $index }}"><th scope="row">{{ $bucket['label'] }}</th><td>{{ number_format($bucket['released'], 2) }}</td><td>{{ number_format($bucket['planned'], 2) }}</td></tr>
                @endforeach</tbody>
            </table></div></details>
        @else
            <div class="empty-log-state"><x-admin.icon name="bar-chart-3" size="28" /><strong>No scheduled payouts for {{ now()->year }}</strong><span>Amounts will appear when payout records are created.</span></div>
        @endif
        <p class="chart-note">Grouped by scheduled date. Scheduled amounts include non-voided payout records; released amounts include records currently marked released.</p>
    </div>
    <div class="panel activity-panel">
        <div class="panel-heading"><div><h3>Recent Activity</h3><p>Latest recorded audit events</p></div><button type="button" class="text-button" wire:click="navigate('system-logs')">View all</button></div>
        <div class="activity-list">
            @forelse ($this->recentActivity as $event)
                <div class="activity-item" wire:key="activity-{{ $event->id }}"><span class="activity-icon blue"><x-admin.icon name="activity" size="15" /></span><div><strong>{{ $event->action }}</strong><small>Audit event #{{ $event->id }}</small></div><time datetime="{{ $event->created_at }}">{{ \Illuminate\Support\Carbon::parse($event->created_at)->diffForHumans() }}</time></div>
            @empty
                <p class="chart-note">No audit activity recorded yet.</p>
            @endforelse
        </div>
    </div>
</section>
<section class="dashboard-grid lower-grid">
    <div class="panel barangay-panel">
        <div class="panel-heading"><div><h3>Registry by Barangay</h3><p>All registered seniors / Share of total registry</p></div></div>
        <div class="barangay-list">
            @forelse ($this->barangayCounts as $barangay)
                @php $share = $summary['seniors'] > 0 ? round($barangay->senior_citizens_count / $summary['seniors'] * 100, 1) : 0; @endphp
                <div class="bar-row" wire:key="barangay-{{ $barangay->id }}"><div><span>{{ $barangay->name }}</span><strong>{{ number_format($barangay->senior_citizens_count) }} <small>({{ $share }}%)</small></strong></div><div class="bar-track" role="meter" aria-label="{{ $barangay->name }} share of registry" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $share }}"><i style="width: {{ $share }}%"></i></div></div>
            @empty
                <p class="chart-note">No barangays recorded yet.</p>
            @endforelse
        </div>
    </div>
    <div class="panel tasks-panel">
        <div class="panel-heading"><div><h3>Needs Attention</h3><p>Current queues from the database</p></div></div>
        <button type="button" class="task-row" wire:click="$set('recordFilter', 'PENDING'); navigate('records')"><span class="task-icon amber"><x-admin.icon name="clipboard-list" size="16" /></span><span><strong>Pending verifications</strong><small>{{ number_format($summary['pending']) }} records</small></span></button>
        <button type="button" class="task-row" wire:click="navigate('sms')"><span class="task-icon blue"><x-admin.icon name="message-square" size="16" /></span><span><strong>SMS queue</strong><small>{{ number_format($summary['sms_queued']) }} queued / {{ number_format($summary['sms_failed']) }} failed</small></span></button>
    </div>
</section>
