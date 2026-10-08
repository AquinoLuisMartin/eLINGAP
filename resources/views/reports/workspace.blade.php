@extends('layouts.app')
@section('content')
<a href="{{ route('reports.index') }}" class="text-sm text-osca-primary underline">Back to reports</a>
<h1 class="mt-3 text-3xl font-bold">{{ $title }}</h1>
<p class="mb-6 text-slate-600">Office for Senior Citizens Affairs · Santa Maria, Bulacan</p>
<form method="GET" class="mb-5 grid gap-3 rounded-lg border bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
    <label>Barangay <select name="barangay_id" class="mt-1 w-full rounded border p-2"><option value="">All barangays</option>@foreach ($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>@endforeach</select></label>
    @if (in_array($type, ['masterlist', 'applications', 'payouts']))
        @php $statuses = match ($type) { 'masterlist' => \App\Enums\SeniorCitizenStatus::cases(), 'applications' => \App\Enums\ApplicationStatus::cases(), default => \App\Enums\PayoutStatus::cases() }; @endphp
        <label>Status <select name="status" class="mt-1 w-full rounded border p-2"><option value="">All statuses</option>@foreach ($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst(strtolower($status->value)) }}</option>@endforeach</select></label>
    @endif
    @if ($type === 'payouts')<label>Program <select name="program_id" class="mt-1 w-full rounded border p-2"><option value="">All programs</option>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->name }}</option>@endforeach</select></label>@endif
    @if (in_array($type, ['applications', 'payouts', 'proxy']))<label>From <input type="date" name="from" value="{{ request('from') }}" class="mt-1 w-full rounded border p-2"></label><label>To <input type="date" name="to" value="{{ request('to') }}" class="mt-1 w-full rounded border p-2"></label>@endif
    @if ($type === 'milestones')<label>Minimum milestone <select name="milestone" class="mt-1 w-full rounded border p-2">@foreach ([80, 90, 100] as $age)<option value="{{ $age }}" @selected(request('milestone', 80) == $age)>{{ $age }}+</option>@endforeach</select></label>@endif
    <div class="flex items-end gap-2"><button class="rounded bg-osca-primary px-4 py-2 text-white">Apply filters</button><a href="{{ route('reports.workspace', $type) }}" class="p-2 underline">Reset</a></div>
</form>
<div class="mb-3 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-slate-600">{{ $records->total() }} records · {{ $filters ? collect($filters)->map(fn ($value, $key) => str_replace('_', ' ', $key).': '.$value)->join(' · ') : 'All records' }}</p><a class="rounded bg-osca-primary px-4 py-2 text-white" href="{{ route('reports.export', ['type' => $type] + request()->query()) }}">Export CSV</a></div>
<div class="overflow-x-auto rounded-lg border bg-white"><table class="w-full min-w-150 text-left text-sm"><thead class="bg-slate-100"><tr>@foreach ($headings as $heading)<th scope="col" class="p-3">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y">@forelse ($records as $record)<tr>@foreach ($report->row($type, $record) as $value)<td class="p-3">{{ $value }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($headings) }}" class="p-6 text-center">No records match these filters.</td></tr>@endforelse</tbody></table></div>
<div class="mt-4">{{ $records->links() }}</div>
@endsection
