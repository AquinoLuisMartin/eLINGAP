@extends('layouts.app')
@section('content')
<x-staff.page-header :title="$title" description="Filter the report below, review the matching records, and export the selected data.">
    <a href="{{ route('reports.index') }}" class="secondary-button">Back to reports</a>
</x-staff.page-header>
<div class="mb-5 flex flex-wrap items-baseline justify-between gap-2 text-sm text-[var(--muted)]">
    <p>Verified senior population: <strong class="text-[var(--ink)]">{{ number_format($population->sum('population')) }}</strong> across all barangays</p>
    <p>As of {{ $generatedAt->format('M j, Y, g:i:s A e') }}</p>
</div>
@if ($type === 'beneficiaries')
    <p class="mb-4 text-sm text-[var(--muted)]">Active program assignments for verified senior citizens. A senior may appear once for each assigned program. Ages are calculated today.</p>
@elseif ($type === 'population')
    <p class="mb-4 text-sm text-[var(--muted)]">Counts verified senior citizens only. Deceased, archived, and transferred records are excluded. Barangays with no verified seniors are included.</p>
@endif
<form id="report-filters" method="GET" action="{{ route('reports.workspace', $type) }}" onchange="if (event.target.matches('select[name], input[name]')) this.requestSubmit()" onkeydown="if (event.key === 'Enter' && event.target.matches('input[name]')) { event.preventDefault(); this.requestSubmit(); }" class="mb-5 grid gap-3 rounded-lg border border-[var(--line)] bg-[var(--white)] p-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="field-label"><label data-select-label>Barangay</label><x-select label="Barangay" name="barangay_id" class="mt-1 w-full"><option value="">All barangays</option>@foreach ($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>@endforeach</x-select></div>
    @if (in_array($type, ['masterlist', 'applications', 'payouts']))
        @php $statuses = match ($type) { 'masterlist' => \App\Enums\SeniorCitizenStatus::cases(), 'applications' => \App\Enums\ApplicationStatus::cases(), default => \App\Enums\PayoutStatus::cases() }; @endphp
        <div class="field-label"><label data-select-label>Status</label><x-select label="Status" name="status" class="mt-1 w-full"><option value="">All statuses</option>@foreach ($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst(strtolower($status->value)) }}</option>@endforeach</x-select></div>
    @endif
    @if (in_array($type, ['beneficiaries', 'payouts']))<div class="field-label"><label data-select-label>Program</label><x-select label="Program" name="program_id" class="mt-1 w-full"><option value="">All programs</option>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->name }}</option>@endforeach</x-select></div>@endif
    @if (in_array($type, ['masterlist', 'beneficiaries']))
        <label class="field-label" for="age_min">Minimum age<input id="age_min" type="number" name="age_min" min="60" max="150" value="{{ request('age_min') }}" class="mt-1 w-full rounded border p-2" placeholder="60"></label>
        <label class="field-label" for="age_max">Maximum age<input id="age_max" type="number" name="age_max" min="60" max="150" value="{{ request('age_max') }}" class="mt-1 w-full rounded border p-2" placeholder="No maximum"></label>
    @endif
    @if (in_array($type, ['applications', 'payouts', 'proxy']))<label>From <input type="date" name="from" value="{{ request('from') }}" class="mt-1 w-full rounded border p-2"></label><label>To <input type="date" name="to" value="{{ request('to') }}" class="mt-1 w-full rounded border p-2"></label>@endif
    @if ($type === 'milestones')<div class="field-label"><label data-select-label>Minimum milestone</label><x-select label="Minimum milestone" name="milestone" class="mt-1 w-full">@forelse ($milestoneAges as $age)<option value="{{ $age }}" @selected(request('milestone', $milestoneAges->first()) == $age)>{{ $age }}+</option>@empty<option value="">No active milestones configured</option>@endforelse</x-select></div>@endif
    <div class="flex items-end"><a href="{{ route('reports.workspace', $type) }}" class="secondary-button">Reset</a></div>
</form>
<div class="mb-3 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-[var(--muted)]">{{ number_format($records->total()) }} records · {{ $filterLabels ? collect($filterLabels)->map(fn ($value, $key) => $key.': '.$value)->join(' · ') : 'All records' }}</p>
    <div class="flex flex-wrap gap-2">
        <x-button type="submit" form="report-filters" formaction="{{ route('reports.export', $type) }}" name="format" value="xlsx">Export Excel (.xlsx)</x-button>
        <x-button type="submit" variant="secondary" form="report-filters" formaction="{{ route('reports.export', $type) }}" name="format" value="csv">Export CSV</x-button>
        <x-button type="submit" variant="secondary" form="report-filters" formaction="{{ route('reports.export', $type) }}" name="format" value="pdf">Download PDF</x-button>
    </div>
</div>
<p class="mb-2 text-sm text-[var(--muted)]">Filters apply automatically. Downloads use the current selections. PDF supports up to {{ number_format(\App\Services\Reports\StaffReportPdf::MAX_RECORDS) }} matching records.</p>
<p class="mb-3 text-xs text-[var(--muted)]">Use Excel (.xlsx) to preserve OSCA IDs as text. Opening CSV directly in Excel may convert IDs to dates.</p>
<x-staff.table label="Report records" class="rounded-lg border border-[var(--line)] bg-[var(--white)]"><table class="w-full min-w-150 text-left text-sm"><caption class="sr-only">{{ $title }}</caption><thead class="bg-slate-100 dark:bg-slate-700"><tr>@foreach ($headings as $heading)<th scope="col" class="p-3">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-[var(--line)]">@forelse ($records as $record)<tr>@foreach ($report->row($type, $record) as $value)<td class="p-3">{{ $value }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($headings) }}" class="p-6 text-center">No records match these filters.</td></tr>@endforelse</tbody></table></x-staff.table>
<div class="mt-4">{{ $records->links() }}</div>
@if ($type !== 'population')
    <details class="panel mt-5">
        <summary class="cursor-pointer text-sm font-semibold">Population by barangay at generation</summary>
        <p class="my-3 text-sm text-[var(--muted)]">Verified senior citizens across all barangays, independent of report filters. Excludes deceased, archived, and transferred records. This summary is included in all downloads.</p>
        <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($population as $barangay)
                <div class="flex justify-between gap-3 border-b border-[var(--line)] py-2 text-sm"><dt>{{ $barangay->name }}</dt><dd class="font-semibold">{{ number_format($barangay->population) }}</dd></div>
            @endforeach
        </dl>
    </details>
@endif
@endsection
