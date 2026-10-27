@extends('layouts.app')
@section('content')
<section class="panel mx-auto max-w-2xl space-y-5">
    <h2 class="text-2xl font-semibold">Review recovery request #{{ $reset->id }}</h2>
    <x-alert />
    <dl class="space-y-3"><div><dt class="font-semibold">Staff member</dt><dd>{{ $reset->user->full_name }} ({{ $reset->user->username }})</dd></div><div><dt class="font-semibold">Requested</dt><dd>{{ $reset->requested_at->format('M j, Y g:i A') }}</dd></div><div><dt class="font-semibold">Status</dt><dd>{{ $reset->expired() ? 'EXPIRED' : $reset->status->value }}</dd></div><div><dt class="font-semibold">Identity verified</dt><dd>{{ $reset->identity_verified_at?->format('M j, Y g:i A') ?? 'Not confirmed' }}</dd></div><div><dt class="font-semibold">Reviewing administrator</dt><dd>{{ $reset->reviewer?->full_name ?? 'Not reviewed' }}</dd></div></dl>
    @if ($reset->status === \App\Enums\PasswordResetStatus::Pending && $reset->user->is_active && $reset->user->isOscaStaff())
        <form method="POST" action="{{ route('administration.password-reset-requests.update', $reset) }}" class="space-y-4">
            @csrf @method('PATCH')
            <label class="flex items-start gap-3"><input type="checkbox" name="identity_verified" value="1"><span>I personally verified this employee's identity using the approved office procedure.</span></label>
            <label class="field-label">Your administrator password<input name="admin_password" type="password" autocomplete="current-password" required></label>
            <div class="flex flex-wrap gap-3"><x-button type="submit" name="decision" value="approve">Verify and approve</x-button><x-button type="submit" name="decision" value="reject" variant="secondary">Reject request</x-button></div>
        </form>
    @else
        <p class="text-sm">This request cannot be approved again. An expired or lost code requires a new reviewed request.</p>
        @can('manageAccess', $reset->user)
            @if ($reset->user->is_active && in_array($reset->status, [\App\Enums\PasswordResetStatus::Expired, \App\Enums\PasswordResetStatus::Rejected, \App\Enums\PasswordResetStatus::Completed], true))
                <form method="POST" action="{{ route('administration.password-reset-requests.store', $reset->user) }}">@csrf<x-button type="submit">Start a new recovery request</x-button></form>
            @endif
        @endcan
    @endif
    <a href="{{ route('administration.password-reset-requests.index') }}" class="text-button">Back to requests</a>
</section>
@endsection
