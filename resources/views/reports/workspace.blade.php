@extends('layouts.app')
@section('content')
<x-staff.page-header :title="$title">
    <a href="{{ route('reports.index') }}" class="primary-button">Back to reports</a>
</x-staff.page-header>
<div class="mb-5 flex flex-wrap items-baseline justify-between gap-2 text-sm text-[var(--muted)]">
    <p>Verified senior population: <strong class="text-[var(--ink)]">{{ number_format($population->sum('population')) }}</strong> across all barangays</p>
    <p>As of {{ $generatedAt->format('M j, Y, g:i:s A e') }}</p>
</div>
<form id="report-filters" method="GET" action="{{ route('reports.workspace', $type) }}" onchange="if (event.target.matches('select[name], input[name]')) this.requestSubmit()" onkeydown="if (event.key === 'Enter' && event.target.matches('input[name]')) { event.preventDefault(); this.requestSubmit(); }" class="mb-5 grid gap-3 rounded-lg border border-[var(--line)] bg-[var(--white)] p-4 sm:grid-cols-2 lg:grid-cols-4">
    <label class="field-label" for="report-search">Search<input id="report-search" type="search" name="search" value="{{ request('search') }}" maxlength="100" placeholder="{{ $type === 'applications' ? 'OSCA ID, name or application reference' : 'OSCA ID or name' }}" class="mt-1 w-full rounded border p-2"></label>
    <div class="field-label"><label data-select-label>Barangay</label><x-select label="Barangay" name="barangay_id" class="mt-1 w-full"><option value="">All barangays</option>@foreach ($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>@endforeach</x-select></div>
    @if (in_array($type, ['masterlist', 'applications', 'payouts', 'archived']))
        @php $statuses = match ($type) { 'masterlist' => \App\Enums\SeniorCitizenStatus::cases(), 'archived' => array_filter(\App\Enums\SeniorCitizenStatus::cases(), fn ($status) => in_array($status->value, ['DECEASED', 'ARCHIVED', 'TRANSFERRED'])), 'applications' => \App\Enums\ApplicationStatus::cases(), default => \App\Enums\PayoutStatus::cases() }; @endphp
        <div class="field-label"><label data-select-label>Status</label><x-select label="Status" name="status" class="mt-1 w-full"><option value="">All statuses</option>@if ($type === 'payouts')@foreach (\App\Services\Reports\PayoutRecords::STATUSES as $value => $label)@if ($value !== '')<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endif @endforeach @else @foreach ($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst(strtolower($status->value)) }}</option>@endforeach @endif</x-select></div>
    @endif
    @if ($type !== 'archived')<div class="field-label"><label data-select-label>Program</label><x-select label="Program" name="program_id" class="mt-1 w-full"><option value="">All programs</option>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->name }}</option>@endforeach</x-select></div>@endif
    @if (in_array($type, ['masterlist', 'beneficiaries', 'population', 'milestones', 'archived']))
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
@if ($summary)
    <dl class="mb-3 flex flex-wrap gap-x-6 gap-y-2 text-sm text-[var(--muted)]">
        @foreach ($summary as $label => $value)<div><dt class="inline">{{ $label }}:</dt> <dd class="inline font-semibold text-[var(--ink)]">{{ is_int($value) ? number_format($value) : $value }}</dd></div>@endforeach
    </dl>
@endif

<x-staff.table label="Report records" class="rounded-lg border border-[var(--line)] bg-[var(--white)]"><table class="w-full min-w-150 text-left text-sm"><caption class="sr-only">{{ $title }}</caption><thead class="bg-slate-100 dark:bg-slate-700"><tr>@foreach ($headings as $heading)<th scope="col" class="p-3">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-[var(--line)]">@forelse ($records as $record)<tr>@foreach ($report->row($type, $record) as $value)<td class="p-3">{{ $value }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($headings) }}" class="p-6 text-center">No records match these filters.</td></tr>@endforelse</tbody></table></x-staff.table>
<div class="mt-4">{{ $records->links() }}</div>
@if ($type !== 'population')
    <details class="panel mt-5">
        <summary class="cursor-pointer text-sm font-semibold">Municipality-wide verified population</summary>
        <dl class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($population as $barangay)
                <div class="flex justify-between gap-3 border-b border-[var(--line)] py-2 text-sm"><dt>{{ $barangay->name }}</dt><dd class="font-semibold">{{ number_format($barangay->population) }}</dd></div>
            @endforeach
        </dl>
    </details>
@endif
@endsection
