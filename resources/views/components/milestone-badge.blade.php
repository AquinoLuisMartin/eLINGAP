@props(['senior'])
@if ($milestone = $senior->milestoneIndicator())
    <span class="ui-label" title="Age {{ $milestone['age'] }}; claim status reflects recorded benefits.">
        {{ $milestone['age'] }}: {{ $milestone['claimed'] ? 'Milestone Claimed' : 'Milestone Reached - Unclaimed' }}
    </span>
@endif
