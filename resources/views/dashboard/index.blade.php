@extends('layouts.app')

@section('content')
<section class="welcome-row">
    <div>
        <p class="section-kicker">{{ now()->format('l, F j, Y') }}</p>
        <h2>Staff dashboard</h2>
        <p class="section-subtitle">Current registry and processing activity across OSCA Santa Maria.</p>
    </div>
</section>

<section class="mb-4 grid gap-3 xl:grid-cols-2" aria-label="Dashboard totals">
    <div class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <h3 class="mb-3 text-base font-semibold">Benefits</h3>
        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-4">
            @foreach (\App\Enums\BenefitStatus::cases() as $state)
                <div class="min-w-0">
                    <dt class="text-sm text-slate-500">{{ $state->label() }}</dt>
                    <dd class="mt-1 text-2xl font-medium tabular-nums">{{ number_format($benefitCounts[$state->value] ?? 0) }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <h3 class="mb-3 text-base font-semibold">Registry overview</h3>
        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-4">
            @foreach ([
                'Active seniors' => [$activeCount, 'Verified records'],
                'Approved applications' => [$readyCount, 'Ready for processing'],
                'Upcoming milestones' => [$milestoneCount, 'Ages 80, 90 and 100'],
                'Barangays covered' => [$barangays->count(), 'Active registry areas'],
            ] as $label => [$count, $description])
                <div class="min-w-0">
                    <dt class="text-sm text-slate-500">{{ $label }}</dt>
                    <dd class="mt-1 text-2xl font-medium tabular-nums">{{ number_format($count) }}<span class="mt-1 block text-xs font-normal text-slate-500">{{ $description }}</span></dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>

<section class="dashboard-grid">
    <div class="panel">
        <div class="panel-heading">
            <div><h3>Population by Barangay</h3><p>Verified senior citizens by service area</p></div>
            <span class="soft-badge blue">{{ number_format($activeCount) }} total</span>
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
                    <span class="task-icon blue"><x-admin.icon name="users" size="15" /></span>
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
                    <span class="task-icon mint"><x-admin.icon name="clipboard-list" size="15" /></span>
                    <div><span><x-badge :status="$status" /></span><small>Registry records</small></div>
                    <strong>{{ number_format($count) }}</strong>
                </div>
            @empty
                <p class="section-subtitle">No data yet.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
