@extends('layouts.app')

@section('content')
<section class="welcome-row">
    <div>
        <p class="section-kicker">{{ now()->format('l, F j, Y') }}</p>
        <h2>Staff dashboard</h2>
        <p class="section-subtitle">Current registry and processing activity across OSCA Santa Maria.</p>
    </div>
    <a href="{{ route('senior-citizens.index', ['status' => 'VERIFIED']) }}" class="primary-button">View senior registry</a>
</section>

<section class="metric-grid">
    <a href="{{ route('senior-citizens.index', ['status' => 'VERIFIED']) }}" class="metric-card">
        <div class="metric-icon blue"><x-admin.icon name="users" size="19" /></div>
        <div class="metric-label">Total Active Seniors</div>
        <div class="metric-value">{{ number_format($activeCount) }}</div>
        <div class="metric-bottom"><span>Verified registry records</span></div>
        <div class="metric-progress blue"><i style="width: {{ $activeCount ? 100 : 0 }}%;"></i></div>
    </a>
    <a href="{{ route('applications.index', ['status' => 'APPROVED']) }}" class="metric-card">
        <div class="metric-icon mint"><x-admin.icon name="clipboard-list" size="19" /></div>
        <div class="metric-label">Approved Applications</div>
        <div class="metric-value">{{ number_format($readyCount) }}</div>
        <div class="metric-bottom"><span>Ready for processing</span></div>
        <div class="metric-progress mint"><i style="width: {{ $readyCount ? 100 : 0 }}%;"></i></div>
    </a>
    <div class="metric-card">
        <div class="metric-icon amber"><x-admin.icon name="calendar-days" size="19" /></div>
        <div class="metric-label">Upcoming Milestones</div>
        <div class="metric-value">{{ number_format($milestoneCount) }}</div>
        <div class="metric-bottom"><span>80, 90 and 100-year milestones</span></div>
        <div class="metric-progress amber"><i style="width: {{ $milestoneCount ? 100 : 0 }}%;"></i></div>
    </div>
    <div class="metric-card">
        <div class="metric-icon violet"><x-admin.icon name="archive" size="19" /></div>
        <div class="metric-label">Barangays Covered</div>
        <div class="metric-value">{{ number_format($barangays->count()) }}</div>
        <div class="metric-bottom"><span>Active areas in the registry</span></div>
        <div class="metric-progress violet"><i style="width: {{ $barangays->count() ? 100 : 0 }}%;"></i></div>
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
                    <div><strong><x-badge :status="$status" /></strong><small>Registry records</small></div>
                    <strong>{{ number_format($count) }}</strong>
                </div>
            @empty
                <p class="section-subtitle">No data yet.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
