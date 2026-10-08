@extends('layouts.app')
@section('content')
<div class="mb-6 flex items-center justify-between gap-3"><div><p class="text-sm font-semibold uppercase text-osca-primary">Registrations</p><h1 class="text-3xl font-bold">Benefit applications</h1></div><a class="rounded bg-osca-primary px-4 py-2 text-white" href="{{ route('applications.create') }}">New application</a></div>
<form method="GET" class="mb-5 grid gap-3 rounded-lg border bg-white p-4 md:grid-cols-4"><label>Search <input name="search" value="{{ request('search') }}" class="mt-1 w-full rounded border p-2" placeholder="Reference or name"></label><label>Status <select name="status" class="mt-1 w-full rounded border p-2"><option value="">All statuses</option>@foreach (\App\Enums\ApplicationStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst(strtolower($status->value)) }}</option>@endforeach</select></label><label>Barangay <select name="barangay_id" class="mt-1 w-full rounded border p-2"><option value="">All barangays</option>@foreach ($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>@endforeach</select></label><div class="flex items-end gap-2"><button class="rounded bg-osca-primary px-4 py-2 text-white">Filter</button><a href="{{ route('applications.index') }}" class="p-2 underline">Reset</a></div></form>
<p class="mb-2 text-sm text-slate-600">{{ $applications->total() }} results</p>
<div class="overflow-x-auto rounded-lg border bg-white"><table class="w-full min-w-150 text-left text-sm"><thead class="bg-slate-100"><tr><th scope="col" class="p-3">Application</th><th scope="col" class="p-3">Applicant</th><th scope="col" class="p-3">Barangay</th><th scope="col" class="p-3">Submitted</th><th scope="col" class="p-3">Program</th><th scope="col" class="p-3">Status</th></tr></thead><tbody class="divide-y">
@forelse ($applications as $application)
<tr><td class="p-3"><a class="text-osca-primary underline" href="{{ route('applications.show', $application) }}">{{ $application->application_number }}</a></td><td class="p-3">{{ $application->seniorCitizen->full_name }}</td><td class="p-3">{{ $application->seniorCitizen->barangay->name }}</td><td class="p-3">{{ $application->applied_on->format('Y-m-d') }}</td><td class="p-3">{{ $application->program->name }}</td><td class="p-3"><x-badge :status="$application->status" /></td></tr>
@empty
<tr><td colspan="6" class="p-6 text-center">No applications match these filters.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-4">{{ $applications->links() }}</div>
@endsection
