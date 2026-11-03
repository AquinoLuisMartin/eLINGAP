@props(['senior'])
@if ($milestone = $senior->milestoneIndicator())
    <span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' => $milestone['claimed'], 'bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-300' => ! $milestone['claimed']]) title="Age {{ $milestone['age'] }}; claim status reflects recorded benefits.">
        {{ $milestone['age'] }}: {{ $milestone['claimed'] ? 'Milestone Claimed' : 'Milestone Reached - Unclaimed' }}
    </span>
@endif
