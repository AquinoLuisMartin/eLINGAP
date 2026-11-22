@extends('layouts.app')
@use('App\Enums\BenefitStatus')

@section('content')
<x-staff.page-header title="Staff dashboard" />

<div class="staff-dashboard grid min-w-0 gap-5 text-[var(--ink)]">
    <dl class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4" aria-label="Registry overview">
        @foreach ([
            ['Verified seniors', $activeCount, 'contact-round'],
            ['Approved applications', $readyCount, 'file-check-2'],
            ['Upcoming milestones', $milestoneCount, 'calendar-days'],
            ['Barangays covered', $barangayCount, 'map-pin'],
        ] as [$label, $count, $icon])
            <div class="grid min-w-0 content-start gap-2 rounded-lg border border-[var(--line)] bg-[var(--white)] p-3 sm:p-4">
                <dt class="flex items-start gap-2 text-sm font-medium text-[var(--muted)]">
                    <x-admin.icon :name="$icon" class="mt-0.5 shrink-0" />
                    {{ $label }}
                </dt>
                <dd class="text-2xl leading-none font-semibold tracking-tight tabular-nums wrap-anywhere sm:text-[2rem]">
                    {{ number_format($count) }}
                </dd>
                @if ($icon === 'calendar-days')
                    <dd class="text-xs text-[var(--muted)]">{{ $milestoneAges->isEmpty() ? 'No milestone programs configured' : 'Ages '.$milestoneAges->join(', ') }}</dd>
                @endif
            </div>
        @endforeach
    </dl>

    <section class="panel" aria-labelledby="benefit-summary-title">
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
            <h3 id="benefit-summary-title" class="text-lg">Benefits</h3>
            <a href="{{ route('benefits.workspace') }}" class="text-button hover:underline">
                View benefits <x-admin.icon name="arrow-up-right" :size="15" />
            </a>
        </div>
        <dl class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
            @foreach (BenefitStatus::cases() as $state)
                @php
                    $dot = match ($state) {
                        BenefitStatus::Pending => 'bg-amber-500',
                        BenefitStatus::Unpaid => 'bg-rose-500',
                        BenefitStatus::Unclaimed => 'bg-blue-500',
                        BenefitStatus::Claimed => 'bg-emerald-500',
                    };
                @endphp
                <div class="min-w-0 rounded-md border border-[var(--line)] bg-[var(--paper)] px-3 py-2.5">
                    <dt class="flex items-center gap-2 text-sm text-[var(--muted)]">
                        <span aria-hidden="true" class="size-2 shrink-0 rounded-full {{ $dot }}"></span>
                        {{ $state->label() }}
                    </dt>
                    <dd class="mt-1 text-xl font-semibold tabular-nums wrap-anywhere">{{ number_format($benefitCounts[$state->value] ?? 0) }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
        @include('dashboard.barangay-population')

        <div class="grid min-w-0 gap-5">
            @include('dashboard.age-distribution')

            <section class="panel" aria-labelledby="gender-distribution-title">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                    <h3 id="gender-distribution-title" class="text-lg">Gender Distribution</h3>
                    <span class="text-sm text-[var(--muted)] tabular-nums">{{ number_format($activeCount) }} total</span>
                </div>
                <div class="mt-4 grid gap-3">
                    @foreach (['MALE' => 'Male', 'FEMALE' => 'Female'] as $sex => $label)
                        @php
                            $count = (int) ($genders[$sex] ?? 0);
                            $percentage = $activeCount ? round(100 * $count / $activeCount, 1) : 0;
                        @endphp
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-x-3 text-sm">
                                <span>{{ $label }}</span>
                                <span class="flex flex-wrap items-center gap-x-3 tabular-nums">
                                    <strong class="font-semibold">{{ number_format($count) }}</strong>
                                    <span class="text-[var(--muted)]">{{ number_format($percentage, 1) }}%</span>
                                </span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-[var(--line)]" role="progressbar" aria-label="{{ $label }} share of verified seniors" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100" aria-valuetext="{{ number_format($count) }} verified seniors ({{ number_format($percentage, 1) }}%)">
                                <span class="block h-full rounded-full {{ $sex === 'MALE' ? 'bg-blue-500' : 'bg-violet-500' }}" style="width: {{ $percentage }}%;"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
