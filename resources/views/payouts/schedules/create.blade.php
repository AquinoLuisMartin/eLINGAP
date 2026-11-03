@extends('layouts.app')
@section('content')
<div x-data="{ masterlistNotice: '', handleGeneratePayoutMasterlist() { this.masterlistNotice = 'Payout masterlist generation is not available yet.'; } }">
<x-workspace-navigation section="payouts" />
<div class="mb-6 flex max-w-2xl items-center justify-between gap-4">
    <h2 class="text-3xl font-semibold">{{ $selectedScheduleId ? 'Review payout schedule' : 'New payout schedule' }}</h2>
    <a href="{{ route('payout-schedules.index') }}" class="secondary-button h-8! min-h-8! shrink-0 px-3! py-1! text-sm!">&larr; Back</a>
</div>
@if ($selectedScheduleId)
    <p class="mb-5 text-lg font-medium">{{ $selectedProgram->name }}</p>
@else
<form method="GET" action="{{ route('payout-schedules.create') }}" class="mb-5 grid max-w-2xl gap-4 rounded-lg border bg-white p-5 dark:bg-slate-900">
    <div class="field-label"><label data-select-label>Program</label>
        <p id="payout-program-help" class="field-helper">Please select a program first to create a payout schedule.</p>
        <x-select label="Program" name="program_id" required aria-describedby="payout-program-help" @change="if ($event.target.value) $event.target.form.requestSubmit()" class="mt-1 block w-full">
            <option value="">Select program</option>
            @foreach ($programs as $program)<option value="{{ $program->id }}" @selected((string) $selectedProgramId === (string) $program->id)>{{ $program->name }}</option>@endforeach
            @if ($canScheduleBenefits)<option value="other" @selected($selectedProgramId === 'other')>Other - enter a new program</option>@endif
        </x-select>
    </div>
</form>
@endif
@if ($useBenefitForm)
    <livewire:benefits.workspace :initial-program-id="(string) $selectedProgramId" :payout-mode="true" :initial-schedule-id="$selectedScheduleId" :key="'payout-program-'.$selectedProgramId.'-'.$selectedScheduleId" />
@elseif ($selectedProgramId)
<form method="POST" action="{{ route('payout-schedules.store') }}" class="grid max-w-2xl gap-4 rounded-lg border bg-white p-5">@csrf
<input type="hidden" name="program_id" value="{{ $selectedProgramId }}">
<label>Reference number <input name="reference_number" value="{{ old('reference_number', 'PAY-'.\Illuminate\Support\Str::ulid()) }}" readonly required maxlength="40" class="mt-1 block w-full rounded border p-2"></label>
<label>Scheduled date <input type="date" name="scheduled_on" value="{{ old('scheduled_on') }}" required class="mt-1 block w-full rounded border p-2"></label>
<label>Amount <input type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" required class="mt-1 block w-full rounded border p-2"></label>
<div class="flex flex-wrap gap-3"><a class="rounded border px-4 py-2" href="{{ route('payout-schedules.index') }}">Cancel</a><x-button type="submit">Create schedule</x-button><button type="button" class="secondary-button" @click="handleGeneratePayoutMasterlist()">Generate Payout Masterlist</button></div>
</form>
@endif
<p x-show="masterlistNotice" x-text="masterlistNotice" x-cloak role="status" class="mt-4 text-sm text-slate-500"></p>
</div>
@endsection
