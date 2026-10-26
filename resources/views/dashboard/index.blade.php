@extends('layouts.app')

@section('content')
<x-staff.page-header title="Staff dashboard" description="Current registry, benefit activity, and records needing attention.">
    <a href="{{ route('senior-citizens.index', ['status' => 'PENDING']) }}" class="primary-button">Review ID applications</a>
    <a href="{{ route('benefits.workspace') }}" class="secondary-button">Open benefits</a>
</x-staff.page-header>

<div class="dashboard-overview">
<dl class="metric-grid" aria-label="Registry overview">
    @foreach ([
        'Active seniors' => [$activeCount, 'Verified records'],
        'Approved applications' => [$readyCount, 'Ready for processing'],
        'Upcoming milestones' => [$milestoneCount, $milestoneAges->isEmpty() ? 'No milestone programs configured' : 'Ages '.$milestoneAges->join(', ')],
        'Barangays covered' => [$barangayCount, 'Active registry areas'],
    ] as $label => [$count, $description])
        <div class="metric-card">
            <dt class="metric-label">{{ $label }}</dt>
            <dd class="metric-value">{{ number_format($count) }}</dd>
            <dd class="metric-bottom">{{ $description }}</dd>
        </div>
    @endforeach
</dl>

<section class="panel dashboard-benefits" aria-labelledby="benefit-summary-title">
    <div class="panel-heading">
        <div><h3 id="benefit-summary-title">Benefits</h3><p>Current entitlement status across all programs</p></div>
        <a href="{{ route('benefits.workspace') }}" class="text-button">View benefits</a>
    </div>
    <dl class="mt-5 grid grid-cols-2 gap-5 sm:grid-cols-4">
        @foreach (\App\Enums\BenefitStatus::cases() as $state)
            <div class="min-w-0">
                <dt class="text-sm text-[var(--muted)]">{{ $state->label() }}</dt>
                <dd class="mt-2 text-2xl font-semibold tabular-nums">{{ number_format($benefitCounts[$state->value] ?? 0) }}</dd>
            </div>
        @endforeach
    </dl>
</section>

<section class="dashboard-grid">
    <div class="panel">
        <div class="panel-heading">
            <div><h3>Population by Barangay</h3><p>Top eight barangays by verified senior population</p></div>
            <span class="soft-badge">{{ number_format($activeCount) }} total</span>
        </div>
        <div class="barangay-list">
            @forelse ($barangays as $barangay)
                <div class="bar-row">
                    <div><span>{{ $barangay->name }}</span><strong>{{ number_format($barangay->total) }}</strong></div>
                    <div class="bar-track"><i style="width: {{ $activeCount ? round(100 * $barangay->total / $activeCount) : 0 }}%;"></i></div>
                </div>
            @empty
                <p class="section-subtitle">No active seniors recorded yet.</p>
            @endforelse
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            <div><h3>Age Distribution</h3><p>Verified registry breakdown</p></div>
        </div>
        <div class="barangay-list">
            @foreach ($ageGroups as $bracket => $count)
                <div class="bar-row">
                    <div><span>{{ $bracket }}</span><strong>{{ number_format($count) }}</strong></div>
                    <div class="bar-track"><i style="width: {{ $activeCount ? round(100 * $count / $activeCount) : 0 }}%;"></i></div>
                </div>
            @endforeach
            <p class="section-subtitle">Total: {{ number_format($ageGroups->sum()) }}</p>
        </div>
    </div>
</section>

<section class="dashboard-grid lower-grid">
    <div class="panel">
        <div class="panel-heading">
            <div><h3>Gender Distribution</h3><p>Current verified records</p></div>
        </div>
        <div class="activity-list">
            @forelse ($genders as $sex => $count)
                <div class="task-row">
                    <div><strong>{{ ucfirst(strtolower($sex)) }}</strong><small>Verified senior citizens</small></div>
                    <strong>{{ number_format($count) }}</strong>
                </div>
            @empty
                <p class="section-subtitle">No data yet.</p>
            @endforelse
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            <div><h3>Registry Status</h3><p>All senior citizen records</p></div>
        </div>
        <div class="activity-list">
            @forelse ($statuses as $status => $count)
                <div class="task-row">
                    <div><span><x-badge :status="$status" /></span><small>Registry records</small></div>
                    <strong>{{ number_format($count) }}</strong>
                </div>
            @empty
                <p class="section-subtitle">No data yet.</p>
            @endforelse
        </div>
    </div>
</section>
</div>
@endsection
