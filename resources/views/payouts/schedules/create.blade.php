@extends('layouts.app')
@section('content')
<x-workspace-navigation section="payouts" />
<h2 class="mb-6 text-3xl font-semibold">{{ $selectedScheduleId ? 'Review payout schedule' : 'New payout schedule' }}</h2>
@if ($selectedScheduleId)
    <p class="mb-5 text-lg font-medium">{{ $selectedProgram->name }}</p>
    <a href="{{ route('payout-schedules.index') }}" class="secondary-button mb-5">Back to payout schedules</a>
@else
<form method="GET" action="{{ route('payout-schedules.create') }}" class="mb-5 grid max-w-2xl gap-4 rounded-lg border bg-white p-5 dark:bg-slate-900">
    <label class="field-label">Program
        <select name="program_id" required class="mt-1 block w-full rounded border p-2">
            <option value="">Select program</option>
            @foreach ($programs as $program)<option value="{{ $program->id }}" @selected((string) $selectedProgramId === (string) $program->id)>{{ $program->name }}</option>@endforeach
            @if ($canScheduleBenefits)<option value="other" @selected($selectedProgramId === 'other')>Other - enter a new program</option>@endif
        </select>
    </label>
    <div><button class="primary-button">Continue with selected program</button></div>
</form>
@endif
@if ($useBenefitForm)
    <livewire:benefits.workspace :initial-program-id="(string) $selectedProgramId" :payout-mode="true" :initial-schedule-id="$selectedScheduleId" :key="'payout-program-'.$selectedProgramId.'-'.$selectedScheduleId" />
@elseif ($selectedProgramId)
<form method="POST" action="{{ route('payout-schedules.store') }}" class="grid max-w-2xl gap-4 rounded-lg border bg-white p-5">@csrf
<input type="hidden" name="program_id" value="{{ $selectedProgramId }}">
<label>Reference number <input name="reference_number" value="{{ old('reference_number') }}" required maxlength="40" class="mt-1 block w-full rounded border p-2"></label>
<label>Scheduled date <input type="date" name="scheduled_on" value="{{ old('scheduled_on') }}" required class="mt-1 block w-full rounded border p-2"></label>
<label>Amount <input type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" required class="mt-1 block w-full rounded border p-2"></label>
<div class="flex gap-3"><a class="rounded border px-4 py-2" href="{{ route('payout-schedules.index') }}">Cancel</a><button class="rounded bg-osca-primary px-4 py-2 text-white">Create schedule</button></div>
</form>
@endif
@endsection
