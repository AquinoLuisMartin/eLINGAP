@extends('layouts.app')
@section('content')
<x-staff.page-header :title="$title" description="Filter the report below, review the matching records, and export the selected data.">
    <a href="{{ route('reports.index') }}" class="secondary-button">Back to reports</a>
</x-staff.page-header>
<form method="GET" class="mb-5 grid gap-3 rounded-lg border bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="field-label"><label data-select-label>Barangay</label><x-select label="Barangay" name="barangay_id" class="mt-1 w-full"><option value="">All barangays</option>@foreach ($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>@endforeach</x-select></div>
    @if (in_array($type, ['masterlist', 'applications', 'payouts']))
        @php $statuses = match ($type) { 'masterlist' => \App\Enums\SeniorCitizenStatus::cases(), 'applications' => \App\Enums\ApplicationStatus::cases(), default => \App\Enums\PayoutStatus::cases() }; @endphp
        <div class="field-label"><label data-select-label>Status</label><x-select label="Status" name="status" class="mt-1 w-full"><option value="">All statuses</option>@foreach ($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst(strtolower($status->value)) }}</option>@endforeach</x-select></div>
    @endif
    @if ($type === 'payouts')<div class="field-label"><label data-select-label>Program</label><x-select label="Program" name="program_id" class="mt-1 w-full"><option value="">All programs</option>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->name }}</option>@endforeach</x-select></div>@endif
    @if (in_array($type, ['applications', 'payouts', 'proxy']))<label>From <input type="date" name="from" value="{{ request('from') }}" class="mt-1 w-full rounded border p-2"></label><label>To <input type="date" name="to" value="{{ request('to') }}" class="mt-1 w-full rounded border p-2"></label>@endif
    @if ($type === 'milestones')<div class="field-label"><label data-select-label>Minimum milestone</label><x-select label="Minimum milestone" name="milestone" class="mt-1 w-full">@forelse ($milestoneAges as $age)<option value="{{ $age }}" @selected(request('milestone', $milestoneAges->first()) == $age)>{{ $age }}+</option>@empty<option value="">No active milestones configured</option>@endforelse</x-select></div>@endif
    <div class="flex items-end gap-2"><x-button type="submit">Apply filters</x-button><a href="{{ route('reports.workspace', $type) }}" class="p-2 underline">Reset</a></div>
</form>
<div class="mb-3 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-slate-600">{{ $records->total() }} records · {{ $filters ? collect($filters)->map(fn ($value, $key) => str_replace('_', ' ', $key).': '.$value)->join(' · ') : 'All records' }}</p><a class="rounded bg-osca-primary px-4 py-2 text-white" href="{{ route('reports.export', ['type' => $type] + request()->query()) }}">Export CSV</a></div>
<x-staff.table label="Report records" class="rounded-lg border bg-white"><table class="w-full min-w-150 text-left text-sm"><caption class="sr-only">{{ $title }}</caption><thead class="bg-slate-100"><tr>@foreach ($headings as $heading)<th scope="col" class="p-3">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y">@forelse ($records as $record)<tr>@foreach ($report->row($type, $record) as $value)<td class="p-3">{{ $value }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($headings) }}" class="p-6 text-center">No records match these filters.</td></tr>@endforelse</tbody></table></x-staff.table>
<div class="mt-4">{{ $records->links() }}</div>
@endsection
