@props(['senior', 'compact' => false])
@if ($milestone = $senior->milestoneIndicator())
    @if ($compact)
        <span class="text-xs text-[var(--muted)]">Age {{ $milestone['age'] }} milestone &middot; {{ $milestone['claimed'] ? 'Claimed' : 'Unclaimed' }}</span>
    @else
        <span class="ui-label" title="Age {{ $milestone['age'] }}; claim status reflects recorded benefits.">
            {{ $milestone['age'] }}: {{ $milestone['claimed'] ? 'Milestone Claimed' : 'Milestone Reached - Unclaimed' }}
        </span>
    @endif
@endif
