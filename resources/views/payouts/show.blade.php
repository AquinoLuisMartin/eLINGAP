@extends('layouts.app')
@section('content')
<a href="{{ route('payouts.index') }}" class="text-sm text-osca-primary underline">Back to payouts</a>
<h1 class="mt-3 text-3xl font-bold">Payout for {{ $payout->seniorCitizen->full_name }}</h1>
<dl class="my-6 grid gap-4 rounded-lg border bg-white p-5 sm:grid-cols-2"><div><dt class="text-sm text-slate-600">OSCA ID</dt><dd>{{ $payout->seniorCitizen->osca_id_number ?: '—' }}</dd></div><div><dt class="text-sm text-slate-600">Barangay</dt><dd>{{ $payout->seniorCitizen->barangay->name }}</dd></div><div><dt class="text-sm text-slate-600">Program</dt><dd>{{ $payout->schedule->program->name }}</dd></div><div><dt class="text-sm text-slate-600">Amount</dt><dd class="font-bold">{{ \App\Support\PhilippineCurrency::format($payout->amount) }}</dd></div><div><dt class="text-sm text-slate-600">Status</dt><dd><x-badge :status="$payout->status" /></dd></div><div><dt class="text-sm text-slate-600">Scheduled</dt><dd>{{ $payout->schedule->scheduled_on }}</dd></div></dl>
@if ($payout->status === \App\Enums\PayoutStatus::Pending)
<form method="POST" action="{{ route('payouts.status.update', $payout) }}" id="payout-release-form" data-beneficiary="{{ $payout->seniorCitizen->full_name }}" data-program="{{ $payout->schedule->program->name }}" data-amount="{{ \App\Support\PhilippineCurrency::format($payout->amount) }}" class="grid max-w-2xl gap-4 rounded-lg border bg-white p-5">
    @csrf @method('PATCH') <input type="hidden" name="status" value="RELEASED">
    <h2 class="text-xl font-semibold">Confirm claimant and release</h2>
    <p>{{ $payout->seniorCitizen->full_name }} · {{ $payout->schedule->program->name }} · {{ \App\Support\PhilippineCurrency::format($payout->amount) }}</p>
    <label>Claimant type <select name="claimant_type" id="claimant-type" class="mt-1 w-full rounded border p-2"><option value="SELF">Senior in person</option><option value="PROXY">Authorized representative</option></select></label>
    <div id="proxy-fields" hidden class="grid gap-3"><label>Representative name <input name="claimant_name" class="mt-1 w-full rounded border p-2"></label><label>Relationship <input name="claimant_relationship" class="mt-1 w-full rounded border p-2"></label><label>Contact number <input name="claimant_contact" class="mt-1 w-full rounded border p-2"></label><label><input type="checkbox" name="authorization_checked" value="1"> Authorization letter checked</label><label><input type="checkbox" name="representative_id_checked" value="1"> Representative government ID checked</label></div>
    <label><input type="checkbox" name="osca_id_checked" value="1" required> {{ $payout->seniorCitizen->osca_id_number ? 'Original OSCA ID checked' : 'Senior identity and registration number checked' }}</label>
    @if ($errors->any())<p class="text-osca-danger">{{ $errors->first() }}</p>@endif
    <button class="rounded bg-osca-primary px-4 py-2 font-semibold text-white" type="submit">Confirm &amp; release payout</button>
</form>
@else
<section class="rounded-lg border bg-white p-5"><h2 class="text-xl font-semibold">Release record</h2><p>Claimant: {{ $payout->claimant_name ?: '—' }} ({{ $payout->claimant_type ?: '—' }})</p><p>Released: {{ $payout->released_at?->format('Y-m-d H:i') ?: '—' }}</p><p>Releasing staff: {{ $payout->releasedBy?->first_name }} {{ $payout->releasedBy?->last_name }}</p>@if ($payout->reversal_reason)<p>Reversal reason: {{ $payout->reversal_reason }}</p>@endif</section>
@endif
@can('reverse', $payout)
    @if ($payout->status === \App\Enums\PayoutStatus::Released)<form method="POST" action="{{ route('payouts.reverse', $payout) }}" class="mt-6 max-w-2xl rounded-lg border border-osca-danger p-5" onsubmit="return confirm('Reverse this released payout?')">@csrf @method('PATCH')<label class="block">Reason for reversal <textarea name="reason" required minlength="10" class="mt-1 w-full rounded border p-2"></textarea></label><button class="mt-3 rounded bg-osca-danger px-4 py-2 text-white">Reverse payout</button></form>@endif
@endcan
@endsection
