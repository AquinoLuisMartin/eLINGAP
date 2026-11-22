@php
    $colors = ['#2563eb', '#0f8b8d', '#7c3aed', '#d97706', '#db2777', '#0284c7', '#65a30d', '#c2410c'];
    $otherTotal = $otherBarangays->sum('total');
    $total = $barangays->sum('total') + $otherTotal;
    $slices = $barangays->map(fn ($barangay, $index) => [$barangay->name, $barangay->total, $colors[$index]]);

    if ($otherTotal > 0) {
        $slices->push(['Other barangays', $otherTotal, '#94a3b8']);
    }

    $stops = [];
    $cumulative = 0;
    foreach ($slices as [$name, $count, $color]) {
        $start = 100 * $cumulative / $total;
        $cumulative += $count;
        $end = 100 * $cumulative / $total;
        $stops[] = "$color $start% $end%";
    }
    $stops[] = 'var(--line) 100%';
@endphp

<section class="panel @container" aria-labelledby="barangay-population-title">
    <h3 id="barangay-population-title" class="text-lg">Population by Barangay</h3>
    <p class="mt-1 text-sm text-[var(--muted)]">
        {{ $otherTotal > 0 ? 'Eight largest barangays by verified senior population' : 'Verified senior population' }}
    </p>

    @if ($total === 0)
        <p class="mt-4 rounded-lg border border-dashed border-[var(--line)] p-4 text-sm text-[var(--muted)]">
            No verified seniors with a barangay recorded yet.
        </p>
    @else
        <div class="mt-5 grid items-center gap-5 @lg:grid-cols-[minmax(0,4fr)_minmax(0,5fr)]">
            <div data-barangay-chart class="relative mx-auto aspect-square w-52 max-w-full rounded-full @lg:w-full @lg:max-w-64" style="background-image: conic-gradient({{ implode(', ', $stops) }});">
                <div class="absolute inset-[17%] grid place-content-center gap-1 rounded-full bg-[var(--white)] px-2 text-center">
                    <span class="text-2xl leading-none font-semibold tabular-nums @xl:text-3xl">{{ number_format($total) }}</span>
                    <span class="text-xs leading-tight text-[var(--muted)]">Verified seniors</span>
                </div>
            </div>

            <dl class="divide-y divide-[var(--line)] text-sm">
                @foreach ($slices as [$name, $count, $color])
                    <div class="grid grid-cols-[minmax(0,1fr)_auto] items-baseline gap-2 py-2 first:pt-0 last:pb-0">
                        <dt class="flex min-w-0 items-baseline gap-2 wrap-anywhere">
                            <span aria-hidden="true" class="size-2.5 shrink-0 rounded-sm" style="background-color: {{ $color }};"></span>
                            {{ $name }}
                        </dt>
                        <dd class="flex items-baseline gap-2 text-right tabular-nums">
                            <strong class="font-semibold">{{ number_format($count) }}</strong>
                            <span class="min-w-[7ch] text-xs text-[var(--muted)]">{{ number_format(100 * $count / $total, 1) }}%</span>
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>

        @if ($otherTotal > 0)
            <details class="mt-5 border-t border-[var(--line)] pt-4">
                <summary class="text-sm font-medium text-[var(--blue)] hover:underline dark:text-blue-300">
                    View counts for all {{ number_format($barangayCount) }} barangays
                </summary>
                <dl class="mt-3 grid gap-x-6 @lg:grid-cols-2">
                    @foreach ($barangays->concat($otherBarangays) as $barangay)
                        <div class="flex justify-between gap-3 border-b border-[var(--line)] py-2 text-sm">
                            <dt class="min-w-0 wrap-anywhere">{{ $barangay->name }}</dt>
                            <dd class="font-semibold tabular-nums">{{ number_format($barangay->total) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </details>
        @endif
    @endif
</section>
