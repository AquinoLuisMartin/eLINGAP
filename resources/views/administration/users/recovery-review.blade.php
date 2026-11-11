@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-staff.page-header :title="'Recovery request #'.$reset->id" description="Verify the employee personally before approving recovery.">
        <a class="secondary-button" href="{{ route('administration.password-reset-requests.index') }}">Back to requests</a>
    </x-staff.page-header>

    <section class="panel">
        <h3 class="mb-5 text-base font-semibold">Request details</h3>
        <dl class="workspace-detail-grid">
            <div><dt>Staff member</dt><dd>{{ $reset->user->full_name }}</dd></div>
            <div><dt>Employee ID</dt><dd>{{ $reset->user->employee_id }}</dd></div>
            <div><dt>Requested</dt><dd>{{ $reset->requested_at->format('M j, Y g:i A') }}</dd></div>
            <div><dt>Status</dt><dd><x-badge :status="$reset->expired() ? 'EXPIRED' : $reset->status" /></dd></div>
            <div><dt>Identity verified</dt><dd>{{ $reset->identity_verified_at?->format('M j, Y g:i A') ?? 'Not confirmed' }}</dd></div>
            <div><dt>Reviewing administrator</dt><dd>{{ $reset->reviewer?->full_name ?? 'Not reviewed' }}</dd></div>
        </dl>
        @if ($reset->status === \App\Enums\PasswordResetStatus::Pending && $reset->user->is_active && $reset->user->isOscaStaff())
            <form method="POST" action="{{ route('administration.password-reset-requests.update', $reset) }}" class="mt-6 flex flex-col gap-5 border-t border-[var(--line)] pt-6">
                @csrf
                @method('PATCH')
                <h3 class="text-base font-semibold">Verify and review</h3>
                <label class="flex items-start gap-3 text-sm leading-relaxed">
                    <input class="mt-1" type="checkbox" name="identity_verified" value="1" @checked(old('identity_verified'))>
                    <span>I personally verified this employee's identity using the approved office procedure.</span>
                </label>
                <div class="ui-field">
                    <label for="admin-password">Your administrator password</label>
                    <input class="w-full" id="admin-password" name="admin_password" type="password" autocomplete="current-password" required>
                    <p class="field-helper">Confirm your identity to approve or reject this request.</p>
                </div>
                <div class="staff-form-actions">
                    <x-button type="submit" name="decision" value="approve">Verify and approve</x-button>
                    <x-button type="submit" name="decision" value="reject" variant="secondary">Reject request</x-button>
                </div>
            </form>
        @else
            <div class="mt-6 flex flex-col items-start gap-4 border-t border-[var(--line)] pt-6">
                <p class="text-sm leading-relaxed text-[var(--muted)]">This request cannot be approved again. An expired or lost code requires a new reviewed request.</p>
                @can('manageAccess', $reset->user)
                    @if ($reset->user->is_active && in_array($reset->status, [\App\Enums\PasswordResetStatus::Expired, \App\Enums\PasswordResetStatus::Rejected, \App\Enums\PasswordResetStatus::Completed], true))
                        <form method="POST" action="{{ route('administration.password-reset-requests.store', $reset->user) }}">
                            @csrf
                            <x-button type="submit">Start a new recovery request</x-button>
                        </form>
                    @endif
                @endcan
            </div>
        @endif
    </section>
</div>
@endsection
