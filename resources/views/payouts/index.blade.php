@extends('layouts.app')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3"><div><p class="text-sm font-semibold uppercase text-osca-primary">Benefits</p><h1 class="text-3xl font-bold">Payouts</h1><p class="text-slate-600">Review beneficiaries and record verified releases.</p></div><a class="text-osca-primary underline" href="{{ route('payout-schedules.index') }}">Payout schedules</a></div>
<div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    @foreach (['Total payouts' => $counts->sum(), 'Unclaimed' => $counts['PENDING'] ?? 0, 'Claimed' => $counts['RELEASED'] ?? 0, 'Claimed via proxy' => $proxyCount] as $label => $count)
        <div class="rounded-lg border bg-white p-4"><p class="text-sm text-slate-600">{{ $label }}</p><p class="text-2xl font-bold">{{ number_format($count) }}</p></div>
    @endforeach
</div>
<form method="GET" class="mb-5 grid gap-3 rounded-lg border bg-white p-4 md:grid-cols-5">
    <label class="text-sm">Search <input name="search" value="{{ request('search') }}" class="mt-1 w-full rounded border p-2" placeholder="Name or OSCA ID"></label>
    <label class="text-sm">Status <select name="status" class="mt-1 w-full rounded border p-2"><option value="">All statuses</option>@foreach (\App\Enums\PayoutStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst(strtolower($status->value)) }}</option>@endforeach</select></label>
    <label class="text-sm">Program <select name="program_id" class="mt-1 w-full rounded border p-2"><option value="">All programs</option>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->name }}</option>@endforeach</select></label>
    <label class="text-sm">Barangay <select name="barangay_id" class="mt-1 w-full rounded border p-2"><option value="">All barangays</option>@foreach ($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>@endforeach</select></label>
    <div class="flex items-end gap-2"><button class="rounded bg-osca-primary px-4 py-2 text-white">Filter</button><a href="{{ route('payouts.index') }}" class="px-2 py-2 underline">Reset</a></div>
</form>
<p class="mb-2 text-sm text-slate-600">{{ $payouts->total() }} results</p>
<div class="overflow-x-auto rounded-lg border bg-white"><table class="w-full min-w-175 text-left text-sm"><thead class="bg-slate-100"><tr>@foreach (['OSCA ID', 'Beneficiary', 'Barangay', 'Program', 'Amount', 'Claimant', 'Status', ''] as $heading)<th scope="col" class="p-3">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y">@forelse ($payouts as $payout)<tr><td class="p-3">{{ $payout->seniorCitizen->osca_id_number ?: '—' }}</td><td class="p-3">{{ $payout->seniorCitizen->full_name }}</td><td class="p-3">{{ $payout->seniorCitizen->barangay->name }}</td><td class="p-3">{{ $payout->schedule->program->name }}</td><td class="p-3">{{ \App\Support\PhilippineCurrency::format($payout->amount) }}</td><td class="p-3">{{ $payout->claimant_name ?: '—' }}</td><td class="p-3"><x-badge :status="$payout->status" /></td><td class="p-3"><a class="text-osca-primary underline" href="{{ route('payouts.show', $payout) }}" aria-label="View payout for {{ $payout->seniorCitizen->full_name }}">View</a></td></tr>@empty<tr><td colspan="8" class="p-6 text-center text-slate-600">No payouts match these filters.</td></tr>@endforelse</tbody></table></div>
<div class="mt-4">{{ $payouts->links() }}</div>
@endsection
