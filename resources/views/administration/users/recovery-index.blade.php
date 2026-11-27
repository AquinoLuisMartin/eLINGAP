@extends('layouts.app')

@section('content')
<div class="recovery-workspace">
    <x-staff.page-header title="Password reset requests" description="Review employee recovery requests and verify identity before granting access.">
        <a class="secondary-button gap-2" href="{{ route('administration.users.index') }}">
            <x-admin.icon name="chevron-left" size="16" /> User accounts
        </a>
    </x-staff.page-header>

    <section class="min-w-0 rounded-lg border border-[var(--line)] bg-[var(--white)]" aria-labelledby="recovery-requests-heading">
        <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-[var(--line)] px-4 py-3 sm:px-5">
            <h3 id="recovery-requests-heading" class="text-base font-semibold">Recovery requests</h3>
            <p class="text-sm text-[var(--muted)] tabular-nums">{{ number_format($requests->total()) }} {{ $requests->total() === 1 ? 'request' : 'requests' }}</p>
        </div>
        <form class="workspace-filter-bar" method="GET" action="{{ route('administration.password-reset-requests.index') }}">
            <div class="ui-field w-full sm:w-60">
                <label class="text-sm font-medium" for="recovery-status">Request status</label>
                <x-select class="w-full" id="recovery-status" name="status">
                    <option value="">All statuses</option>
                    @foreach (\App\Enums\PasswordResetStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst(strtolower($status->value)) }}</option>
                    @endforeach
                </x-select>
            </div>
            <x-button type="submit" variant="secondary" class="min-h-11 gap-2">
                <x-admin.icon name="sliders-horizontal" size="16" /> Apply filter
            </x-button>
            @if (request()->filled('status'))
                <a class="workspace-text-link" href="{{ route('administration.password-reset-requests.index') }}">Clear filter</a>
            @endif
        </form>

        <x-staff.table label="Password reset requests">
            <table class="w-full min-w-224 text-left text-sm [&_td]:align-middle [&_th]:bg-[var(--paper)]">
                <caption class="sr-only">Employee account recovery requests and review status.</caption>
                <thead>
                    <tr>
                        <th scope="col">Request</th>
                        <th scope="col">Employee</th>
                        <th scope="col">Requested</th>
                        <th scope="col">Status</th>
                        <th scope="col">Identity verified</th>
                        <th scope="col">Reviewer</th>
                        <th scope="col" class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $reset)
                        <tr>
                            <td class="whitespace-nowrap tabular-nums">#{{ $reset->id }}</td>
                            <td>
                                <span class="block font-medium">{{ $reset->user->full_name }}</span>
                                <span class="block text-xs text-[var(--muted)]">{{ $reset->user->employee_id }}</span>
                            </td>
                            <td class="whitespace-nowrap">{{ $reset->requested_at->format('M j, Y g:i A') }}</td>
                            @php
                                $status = $reset->expired() ? \App\Enums\PasswordResetStatus::Expired : $reset->status;
                                $icon = match ($status) {
                                    \App\Enums\PasswordResetStatus::Pending => 'clock-3',
                                    \App\Enums\PasswordResetStatus::Approved => 'shield-check',
                                    \App\Enums\PasswordResetStatus::Completed => 'check',
                                    \App\Enums\PasswordResetStatus::Rejected => 'x',
                                    \App\Enums\PasswordResetStatus::Expired => 'archive',
                                };
                            @endphp
                            <td>
                                <span class="ui-label gap-1.5">
                                    <x-admin.icon :name="$icon" size="14" class="shrink-0 text-[var(--muted)]" />
                                    {{ ucfirst(strtolower($status->value)) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap">{{ $reset->identity_verified_at?->format('M j, Y g:i A') ?? 'Not confirmed' }}</td>
                            <td>{{ $reset->reviewer?->full_name ?? 'Not reviewed' }}</td>
                            <td class="text-right">
                                <a class="text-button min-h-9 gap-1.5 whitespace-nowrap hover:underline" href="{{ route('administration.password-reset-requests.show', $reset) }}">
                                    {{ $reset->status === \App\Enums\PasswordResetStatus::Pending ? 'Review' : 'View details' }}<span class="sr-only"> request #{{ $reset->id }}</span>
                                    <x-admin.icon name="chevron-right" size="15" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-8 text-center text-[var(--muted)]">No password reset requests recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-staff.table>
        @if ($requests->hasPages())
            <div class="border-t border-[var(--line)] px-4 py-3 sm:px-5">{{ $requests->links() }}</div>
        @endif
    </section>
</div>
@endsection
