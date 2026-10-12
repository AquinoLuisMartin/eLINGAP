<section class="page-intro"><div><p class="section-kicker">Program registry</p><h2>Benefits &amp; Programs</h2><p class="section-subtitle">Recorded budgets, beneficiary enrollments, and released payouts.</p></div><a class="primary-button" href="{{ route('benefits.programs') }}"><x-admin.icon name="plus" size="17" /> Review Program Rules</a></section>
<div class="program-grid" wire:loading.class="data-refreshing" wire:target="search">
    @forelse ($this->programs as $program)
        @php $released = (float) $program->released_amount + (float) $program->benefit_released_amount; $utilization = (float) $program->budget > 0 ? round($released / (float) $program->budget * 100, 1) : null; @endphp
        <div class="panel program-card" wire:key="program-{{ $program->id }}">
            <div class="program-card-top"><span class="soft-badge {{ $program->status === 'ACTIVE' ? 'mint' : 'amber' }}">{{ ucfirst(strtolower($program->status)) }}</span><span class="chart-note">{{ number_format($program->benefit_code ? $program->benefit_entitlements_count : $program->beneficiaries_count) }} {{ $program->benefit_code ? 'entitlements' : 'historical enrollments' }}</span></div>
            <h3>{{ $program->name }}</h3><p>{{ $program->agency }}</p>
            <div class="program-meta"><span>Budget allocation<strong>PHP {{ number_format((float) $program->budget, 2) }}</strong></span><span>Program dates<strong>{{ $program->starts_on?->format('M j, Y') ?? 'Not set' }} to {{ $program->ends_on?->format('M j, Y') ?? 'Not set' }}</strong></span></div>
            <div class="budget-line"><div><span>Released / Budget</span><strong>{{ $utilization === null ? 'No budget set' : $utilization.'%' }}</strong></div><div class="bar-track"><i style="width: {{ min(100, max(0, $utilization ?? 0)) }}%"></i></div><p class="chart-note">PHP {{ number_format($released, 2) }} released</p></div>
            <a class="text-button" href="{{ route('programs.show', $program) }}">View program details <x-admin.icon name="chevron-right" size="15" /></a>
        </div>
    @empty
        <div class="panel empty-module"><h3>No matching programs</h3><p>Create a program or adjust your search.</p></div>
    @endforelse
</div>
{{ $this->programs->links('administration.dashboard.pagination') }}
