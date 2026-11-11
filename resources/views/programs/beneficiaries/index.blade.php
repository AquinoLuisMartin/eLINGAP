@extends('layouts.app')

@section('content')
<x-staff.page-header :title="$program->name.' beneficiaries'" description="Review enrolled beneficiaries and add eligible senior citizens.">
    <a class="secondary-button" href="{{ route('programs.show', $program) }}">Back to program</a>
</x-staff.page-header>
@can('manage', \App\Models\BenefitSchedule::class)
    <form method="GET" class="staff-filter-panel flex flex-wrap items-end gap-3">
        <label class="ui-field grow">Find a senior with an issued ID
            <input class="w-full" name="senior_search" value="{{ request('senior_search') }}" placeholder="Name or OSCA ID" maxlength="100" required>
        </label>
        <x-button type="submit" variant="secondary">Search</x-button>
    </form>
    <form method="POST" action="{{ route('programs.beneficiaries.store', $program) }}" class="panel mb-6 grid max-w-2xl gap-5">
        @csrf
        <input type="hidden" name="program_id" value="{{ $program->id }}">
        <h3 class="text-base font-semibold">Enroll a beneficiary</h3>
        <div class="ui-field">
            <label for="beneficiary-senior">Senior citizen</label>
            <x-select class="w-full" id="beneficiary-senior" name="senior_citizen_id" required>
                <option value="">Select senior citizen</option>
                @foreach ($seniorCitizens as $senior)
                    <option value="{{ $senior->id }}" @selected(old('senior_citizen_id') == $senior->id)>{{ $senior->full_name }} ({{ $senior->osca_id_number ?: 'Not issued' }})</option>
                @endforeach
            </x-select>
        </div>
        <label class="ui-field">Enrolled on<input class="w-full" type="date" name="enrolled_on" value="{{ old('enrolled_on', today()->format('Y-m-d')) }}" required></label>
        <div class="staff-form-actions"><x-button type="submit">Enroll beneficiary</x-button></div>
    </form>
@endcan
<p class="mb-3 text-sm text-[var(--muted)]">{{ $beneficiaries->total() }} beneficiaries</p>
<x-staff.table label="Program beneficiaries" class="rounded-lg border bg-white">
    <table class="w-full text-left text-sm">
        <thead><tr><th scope="col">Senior</th><th scope="col">Barangay</th><th scope="col">Enrolled</th><th scope="col">Status</th></tr></thead>
        <tbody>
            @forelse ($beneficiaries as $beneficiary)
                <tr><td><a class="workspace-text-link" href="{{ route('senior-citizens.show', $beneficiary->seniorCitizen) }}">{{ $beneficiary->seniorCitizen->full_name }}</a></td><td>{{ $beneficiary->seniorCitizen->barangay->name }}</td><td>{{ $beneficiary->enrolled_on->format('M j, Y') }}</td><td><x-badge :status="$beneficiary->status" /></td></tr>
            @empty
                <tr><td colspan="4" class="p-6 text-center">No beneficiaries enrolled.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-staff.table>
<div class="mt-4">{{ $beneficiaries->links() }}</div>
@endsection
