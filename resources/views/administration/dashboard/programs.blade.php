{{-- Benefits and Programs Page --}}
<section class="page-intro">
    <div>
        <p class="section-kicker">Program registry</p>
        <h2>Benefits &amp; Programs</h2>
        <p class="section-subtitle">Track active government programs, funding, and payout cycles.</p>
    </div>
    <button type="button" class="primary-button" wire:click="openModal('program')">
        <x-admin.icon name="plus" size="17" /> Create Program
    </button>
</section>

<div class="program-grid">
    @foreach ($programList as $program)
        <div class="panel program-card" wire:key="program-{{ $program['name'] }}">
            <div class="program-card-top">
                <span class="soft-badge {{ $program['status'] === 'Upcoming' ? 'amber' : 'mint' }}">
                    {{ $program['status'] }}
                </span>
                <button type="button" class="icon-button" aria-label="Program options">
                    <x-admin.icon name="more-horizontal" size="17" />
                </button>
            </div>
            <h3>{{ $program['name'] }}</h3>
            <p>{{ $program['agency'] }}</p>
            <div class="program-meta">
                <span>
                    Budget allocation
                    <strong>{{ $program['budget'] }}</strong>
                </span>
                <span>
                    Cycle
                    <strong>{{ $program['cycle'] }}</strong>
                </span>
            </div>
            <div class="budget-line">
                <div>
                    <span>Budget utilized</span>
                    <strong>{{ $program['used'] }}%</strong>
                </div>
                <div class="bar-track">
                    <i style="width: {{ $program['used'] }}%;"></i>
                </div>
            </div>
            <button type="button" class="text-button">
                View program details <x-admin.icon name="chevron-right" size="15" />
            </button>
        </div>
    @endforeach
</div>
