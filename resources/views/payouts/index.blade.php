@extends('layouts.app')
@section('content')
<x-workspace-navigation section="payouts" />
<x-staff.page-header title="Payouts" description="Review beneficiaries, filter payout records, and record verified releases.">
    <a href="{{ route('payout-schedules.index') }}" class="secondary-button">View schedules</a>
</x-staff.page-header>
<dl class="staff-summary-grid mb-5 grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-[var(--line)] bg-[var(--line)] lg:grid-cols-4">
    @foreach (['Total payouts' => $counts->sum(), 'Unclaimed' => $counts['PENDING'] ?? 0, 'Claimed' => $counts['RELEASED'] ?? 0, 'Claimed via proxy' => $proxyCount] as $label => $count)
        <div class="staff-summary-item bg-[var(--white)] p-4"><dt class="text-sm text-[var(--muted)]">{{ $label }}</dt><dd class="mt-1 text-2xl font-semibold">{{ number_format($count) }}</dd></div>
    @endforeach
</dl>
<form method="GET" class="mb-5 grid gap-4 rounded-lg border border-[var(--line)] bg-[var(--white)] p-4 sm:grid-cols-2 xl:grid-cols-5">
    <label class="text-sm">Search <input name="search" value="{{ request('search') }}" class="mt-1 w-full rounded border p-2" placeholder="Name or OSCA ID"></label>
    <div class="field-label text-sm"><label data-select-label>Status</label><x-select label="Status" name="status" class="mt-1 w-full"><option value="">All statuses</option>@foreach (\App\Enums\PayoutStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst(strtolower($status->value)) }}</option>@endforeach</x-select></div>
    <div class="field-label text-sm"><label data-select-label>Program</label><x-select label="Program" name="program_id" class="mt-1 w-full"><option value="">All programs</option>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->name }}</option>@endforeach</x-select></div>
    @include('senior-citizens._barangay-filter', ['barangays' => $barangays, 'filterId' => 'payout-barangay'])
    <div class="flex flex-wrap items-end gap-2 sm:col-span-2 xl:col-span-1"><button type="submit" class="primary-button">Filter</button><a href="{{ route('payouts.index') }}" class="secondary-button">Reset</a></div>
</form>
<p class="mb-2 text-sm text-slate-600">{{ $payouts->total() }} results</p>
<x-staff.table label="Payout records" class="rounded-lg border bg-white"><table class="w-full min-w-175 text-left text-sm"><thead class="bg-slate-100"><tr>@foreach (['OSCA ID', 'Beneficiary', 'Barangay', 'Program', 'Amount', 'Claimant', 'Status', ''] as $heading)<th scope="col" class="p-3">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y">@forelse ($payouts as $payout)<tr><td class="p-3">{{ $payout->seniorCitizen->osca_id_number ?: '—' }}</td><td class="p-3">{{ $payout->seniorCitizen->full_name }}</td><td class="p-3">{{ $payout->seniorCitizen->barangay->name }}</td><td class="p-3">{{ $payout->schedule->program->name }}</td><td class="p-3">{{ \App\Support\PhilippineCurrency::format($payout->amount) }}</td><td class="p-3">{{ $payout->claimant_name ?: '—' }}</td><td class="p-3"><x-badge :status="$payout->status" /></td><td class="p-3"><a class="text-osca-primary underline" href="{{ route('payouts.show', $payout) }}" aria-label="View payout for {{ $payout->seniorCitizen->full_name }}">View</a></td></tr>@empty<tr><td colspan="8" class="p-6 text-center text-slate-600">No payouts match these filters.</td></tr>@endforelse</tbody></table></x-staff.table>
<div class="mt-4">{{ $payouts->links() }}</div>
@endsection
