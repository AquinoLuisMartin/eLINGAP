@extends('layouts.app')
@section('content')
<h1 class="mb-6 text-3xl font-bold">New payout schedule</h1>
<form method="POST" action="{{ route('payout-schedules.store') }}" class="grid max-w-2xl gap-4 rounded-lg border bg-white p-5">@csrf
<label>Program <select name="program_id" required class="mt-1 block w-full rounded border p-2"><option value="">Select program</option>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected(old('program_id') == $program->id)>{{ $program->name }}</option>@endforeach</select></label>
<label>Reference number <input name="reference_number" value="{{ old('reference_number') }}" required maxlength="40" class="mt-1 block w-full rounded border p-2"></label>
<label>Scheduled date <input type="date" name="scheduled_on" value="{{ old('scheduled_on') }}" required class="mt-1 block w-full rounded border p-2"></label>
<label>Amount <input type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" required class="mt-1 block w-full rounded border p-2"></label>
<div class="flex gap-3"><a class="rounded border px-4 py-2" href="{{ route('payout-schedules.index') }}">Cancel</a><button class="rounded bg-osca-primary px-4 py-2 text-white">Create schedule</button></div>
</form>
@endsection
