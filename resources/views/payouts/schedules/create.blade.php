@extends('layouts.app')
@section('content')
<x-workspace-navigation section="payouts" />
<div class="mx-auto w-full min-w-0 max-w-4xl space-y-5">
<x-staff.page-header :title="$selectedScheduleId ? 'Review payout schedule' : 'New payout schedule'" :description="$selectedScheduleId ? 'Review recipients and funding before approving the schedule.' : 'Choose a program, enter schedule details, then review eligible recipients.'">
    <a href="{{ route('payout-schedules.index') }}" class="secondary-button">&larr; Back to schedules</a>
</x-staff.page-header>
@if ($selectedScheduleId)
    <p class="mb-5 text-lg font-medium">{{ $selectedProgram->name }}</p>
@else
<form method="GET" action="{{ route('payout-schedules.create') }}" class="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900 sm:p-6">
    <div class="field-label"><label data-select-label>Program</label>
        <p id="payout-program-help" class="field-helper">Select a program and continue to its schedule details.</p>
        <x-select id="payout-program" label="Program" name="program_id" required aria-describedby="payout-program-help" class="mt-2 block w-full">
            <option value="">Select program</option>
            @foreach ($programs as $program)<option value="{{ $program->id }}" @selected((string) $selectedProgramId === (string) $program->id)>{{ $program->name }}</option>@endforeach
            @if ($canScheduleBenefits)<option value="other" @selected($selectedProgramId === 'other')>Other - enter a new program</option>@endif
        </x-select>
    </div>
    <div class="flex justify-end"><button type="submit" class="secondary-button w-full sm:w-auto">{{ $selectedProgramId ? 'Change program' : 'Continue to schedule details' }}</button></div>
</form>
@endif
@if ($selectedProgramId && $useBenefitForm)
    <livewire:benefits.workspace :initial-program-id="(string) $selectedProgramId" :payout-mode="true" :initial-schedule-id="$selectedScheduleId" :key="'payout-program-'.$selectedProgramId.'-'.$selectedScheduleId" />
@elseif ($selectedProgramId)
<form method="POST" action="{{ route('payout-schedules.store') }}" class="grid gap-5 rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900 sm:p-6">@csrf
<h3 class="text-lg font-semibold">Schedule details</h3>
<input type="hidden" name="program_id" value="{{ $selectedProgramId }}">
<label>Reference number <input name="reference_number" value="{{ old('reference_number', 'PAY-'.\Illuminate\Support\Str::ulid()) }}" readonly required maxlength="40" class="mt-1 block w-full rounded border p-2"></label>
<label>Scheduled date <input type="date" name="scheduled_on" value="{{ old('scheduled_on') }}" required class="mt-1 block w-full rounded border p-2"></label>
<label>Amount <input type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" required class="mt-1 block w-full rounded border p-2"></label>
<div class="grid gap-3 border-t border-slate-200 pt-4 dark:border-slate-700 sm:flex sm:justify-end"><a class="secondary-button" href="{{ route('payout-schedules.index') }}">Cancel</a><x-button type="submit">Create schedule</x-button></div>
</form>
@endif
</div>
@endsection
