@extends('layouts.app')

@section('content')
<x-workspace-navigation section="payouts" />
<x-staff.page-header title="Payout details" description="Check beneficiary information and confirm the release requirements.">
    <a class="secondary-button" href="{{ route('payouts.index') }}">Back to payouts</a>
</x-staff.page-header>
<section class="panel mb-6">
    <dl class="workspace-detail-grid">
        <div><dt>Beneficiary</dt><dd>{{ $payout->seniorCitizen->full_name }}</dd></div>
        <div><dt>OSCA ID</dt><dd>{{ $payout->seniorCitizen->osca_id_number ?: 'Not issued' }}</dd></div>
        <div><dt>Barangay</dt><dd>{{ $payout->seniorCitizen->barangay->name }}</dd></div>
        <div><dt>Program</dt><dd>{{ $payout->schedule->program->name }}</dd></div>
        <div><dt>Amount</dt><dd>{{ \App\Support\PhilippineCurrency::format($payout->amount) }}</dd></div>
        <div><dt>Status</dt><dd><x-badge :status="$payout->status" /></dd></div>
        <div><dt>Scheduled</dt><dd>{{ $payout->schedule->scheduled_on->format('M j, Y') }}</dd></div>
    </dl>
</section>
@if ($payout->status === \App\Enums\PayoutStatus::Pending)
    <form method="POST" action="{{ route('payouts.status.update', $payout) }}" id="payout-release-form" data-beneficiary="{{ $payout->seniorCitizen->full_name }}" data-program="{{ $payout->schedule->program->name }}" data-amount="{{ \App\Support\PhilippineCurrency::format($payout->amount) }}" class="panel grid max-w-2xl gap-5">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" value="RELEASED">
        <h3 class="text-base font-semibold">Confirm claimant and release</h3>
        <div class="ui-field">
            <label for="claimant-type">Claimant type</label>
            <x-select class="w-full" name="claimant_type" id="claimant-type">
                <option value="SELF" @selected(old('claimant_type') === 'SELF')>Senior in person</option>
                <option value="PROXY" @selected(old('claimant_type') === 'PROXY')>Authorized representative</option>
            </x-select>
        </div>
        <div id="proxy-fields" hidden class="grid gap-4">
            <label class="ui-field">Representative name<input class="w-full" type="text" name="claimant_name" value="{{ old('claimant_name') }}" maxlength="200"></label>
            <label class="ui-field">Relationship<input class="w-full" type="text" name="claimant_relationship" value="{{ old('claimant_relationship') }}" maxlength="100"></label>
            <label class="ui-field">Contact number<input class="w-full" type="text" inputmode="tel" name="claimant_contact" value="{{ old('claimant_contact') }}" maxlength="30"></label>
            <label class="flex items-start gap-3"><input class="mt-1" type="checkbox" name="authorization_checked" value="1" @checked(old('authorization_checked'))><span>Authorization letter checked</span></label>
            <label class="flex items-start gap-3"><input class="mt-1" type="checkbox" name="representative_id_checked" value="1" @checked(old('representative_id_checked'))><span>Representative government ID checked</span></label>
        </div>
        <label class="flex items-start gap-3"><input class="mt-1" type="checkbox" name="osca_id_checked" value="1" required @checked(old('osca_id_checked'))><span>{{ $payout->seniorCitizen->osca_id_number ? 'Original OSCA ID checked' : 'Senior identity checked' }}</span></label>
        <div class="staff-form-actions"><x-button type="submit">Confirm &amp; release payout</x-button></div>
    </form>
@else
    <section class="panel">
        <h3 class="mb-5 text-base font-semibold">Release record</h3>
        <dl class="workspace-detail-grid">
            <div><dt>Claimant</dt><dd>{{ $payout->claimant_name ?: 'Not recorded' }} ({{ $payout->claimant_type ?: 'Not recorded' }})</dd></div>
            <div><dt>Released</dt><dd>{{ $payout->released_at?->format('M j, Y g:i A') ?: 'Not released' }}</dd></div>
            <div><dt>Releasing staff</dt><dd>{{ $payout->releasedBy?->full_name ?? 'Not recorded' }}</dd></div>
            @if ($payout->reversal_reason)<div><dt>Reversal reason</dt><dd>{{ $payout->reversal_reason }}</dd></div>@endif
        </dl>
    </section>
@endif
@can('reverse', $payout)
    @if ($payout->status === \App\Enums\PayoutStatus::Released)
        <form method="POST" action="{{ route('payouts.reverse', $payout) }}" class="panel mt-6 grid max-w-2xl gap-5" data-confirm="The original release stays in the audit history." data-confirm-title="Reverse this released payout?" data-confirm-label="Reverse payout" data-confirm-tone="danger">
            @csrf
            @method('PATCH')
            <h3 class="text-base font-semibold">Reverse payout</h3>
            <label class="ui-field">Reason for reversal<textarea class="w-full" name="reason" rows="4" required minlength="10" maxlength="1000">{{ old('reason') }}</textarea></label>
            <div class="staff-form-actions"><x-button type="submit" variant="danger">Reverse payout</x-button></div>
        </form>
    @endif
@endcan
@endsection
