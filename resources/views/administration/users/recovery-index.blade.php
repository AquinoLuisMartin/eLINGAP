@extends('layouts.app')

@section('content')
<div class="recovery-workspace">
    <x-staff.page-header title="Password reset requests" description="Review employee recovery requests and verify identity before granting access.">
        <a class="secondary-button" href="{{ route('administration.users.index') }}">User accounts</a>
    </x-staff.page-header>

    <section class="staff-results-panel rounded-lg border bg-white" aria-labelledby="recovery-requests-heading">
        <form class="workspace-filter-bar" method="GET" action="{{ route('administration.password-reset-requests.index') }}">
            <div class="ui-field w-full sm:w-60">
                <label for="recovery-status">Request status</label>
                <x-select class="w-full" id="recovery-status" name="status">
                    <option value="">All statuses</option>
                    @foreach (\App\Enums\PasswordResetStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst(strtolower($status->value)) }}</option>
                    @endforeach
                </x-select>
            </div>
            <x-button type="submit" variant="secondary">Apply filter</x-button>
            @if (request()->filled('status'))
                <a class="workspace-text-link" href="{{ route('administration.password-reset-requests.index') }}">Clear filter</a>
            @endif
        </form>

        <div class="staff-panel-heading flex flex-wrap items-center justify-between gap-3">
            <h3 id="recovery-requests-heading">Recovery requests</h3>
            <p>{{ number_format($requests->total()) }} {{ $requests->total() === 1 ? 'request' : 'requests' }}</p>
        </div>
        <x-staff.table label="Password reset requests">
            <table class="w-full min-w-240 text-left text-sm">
                <caption class="sr-only">Employee account recovery requests and review status.</caption>
                <thead class="bg-slate-50">
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
                            <td><x-badge :status="$reset->expired() ? 'EXPIRED' : $reset->status" /></td>
                            <td class="whitespace-nowrap">{{ $reset->identity_verified_at?->format('M j, Y g:i A') ?? 'Not confirmed' }}</td>
                            <td>{{ $reset->reviewer?->full_name ?? 'Not reviewed' }}</td>
                            <td class="text-right">
                                <a class="workspace-text-link whitespace-nowrap" href="{{ route('administration.password-reset-requests.show', $reset) }}">
                                    {{ $reset->status === \App\Enums\PasswordResetStatus::Pending ? 'Review' : 'View details' }}<span class="sr-only"> request #{{ $reset->id }}</span>
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
            <div class="staff-pagination">{{ $requests->links() }}</div>
        @endif
    </section>
</div>
@endsection
