@php($ageTotal = $ageGroups->sum('total'))

<section class="panel" aria-labelledby="age-distribution-title">
    <h3 id="age-distribution-title" class="text-lg">Age Distribution</h3>
    <p class="mt-1 text-sm text-[var(--muted)]">Verified seniors aged 60 and older</p>

    @if ($ageTotal === 0)
        <p class="mt-4 rounded-lg border border-dashed border-[var(--line)] p-4 text-sm text-[var(--muted)]">
            No verified seniors in these age groups yet.
        </p>
    @else
        <dl class="mt-4 grid gap-3">
            @foreach ($ageGroups as $bracket => $counts)
                @php($percentage = 100 * $counts['total'] / $ageTotal)
                <div class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-x-3 gap-y-1 text-sm">
                    <dt class="w-[6ch] font-medium">{{ $bracket }}</dt>
                    <dd class="contents tabular-nums">
                        <span aria-hidden="true" class="h-2.5 overflow-hidden rounded-full bg-[var(--line)]">
                            <span class="block h-full rounded-full bg-blue-600 dark:bg-blue-400" style="width: {{ $percentage }}%;"></span>
                        </span>
                        <span class="flex flex-wrap items-baseline justify-end gap-x-2 text-right">
                            <strong class="font-semibold">{{ number_format($counts['total']) }}</strong>
                            <span class="min-w-[7ch] text-xs text-[var(--muted)]">{{ number_format($percentage, 1) }}%</span>
                        </span>
                        <span class="col-span-2 col-start-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-[var(--muted)]">
                            <span>Male <strong class="font-semibold text-[var(--ink)]">{{ number_format($counts['male']) }}</strong></span>
                            <span>Female <strong class="font-semibold text-[var(--ink)]">{{ number_format($counts['female']) }}</strong></span>
                        </span>
                    </dd>
                </div>
            @endforeach
        </dl>
        <p class="mt-4 border-t border-[var(--line)] pt-3 text-sm text-[var(--muted)] tabular-nums">
            {{ number_format($ageTotal) }} total across age groups
        </p>
    @endif
</section>
