@extends('layouts.app')
@section('content')
<div class="space-y-5">
    <x-staff.page-header title="Password Reset Requests" description="Verify the employee personally before approving recovery."><a class="secondary-button" href="{{ route('administration.users.index') }}">Account Management</a></x-staff.page-header>
    <x-alert />
    <form method="GET" class="flex flex-wrap items-end gap-3"><label class="field-label">Status<x-select name="status" label="Request status"><option value="">All statuses</option>@foreach (\App\Enums\PasswordResetStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst(strtolower($status->value)) }}</option>@endforeach</x-select></label><x-button type="submit">Filter</x-button></form>
    <div class="panel table-panel"><div class="table-scroll" role="region" aria-label="Password Reset Requests" tabindex="0"><table><thead><tr><th>Request</th><th>Username / staff name</th><th>Requested</th><th>Status</th><th>Identity verified</th><th>Reviewer</th><th>Action</th></tr></thead><tbody>
        @forelse ($requests as $reset)
            <tr><td>{{ $reset->id }}</td><td>{{ $reset->user->username }}<br>{{ $reset->user->full_name }}</td><td>{{ $reset->requested_at->format('M j, Y g:i A') }}</td><td>{{ $reset->expired() ? 'EXPIRED' : $reset->status->value }}</td><td>{{ $reset->identity_verified_at?->format('M j, Y g:i A') ?? 'Not confirmed' }}</td><td>{{ $reset->reviewer?->full_name ?? 'Not reviewed' }}</td><td><a href="{{ route('administration.password-reset-requests.show', $reset) }}" class="text-button">Review</a></td></tr>
        @empty
            <tr><td colspan="7">No password reset requests recorded.</td></tr>
        @endforelse
    </tbody></table></div></div>
    {{ $requests->links() }}
</div>
@endsection
