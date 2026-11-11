@extends('layouts.app')

@section('content')
<x-workspace-navigation section="payouts" />
<x-staff.page-header :title="$schedule->reference_number" :description="$schedule->program->name">
    <a class="secondary-button" href="{{ route('payout-schedules.index') }}">Back to schedules</a>
</x-staff.page-header>
<section class="panel mb-5">
    <dl class="workspace-detail-grid">
        <div><dt>Scheduled date</dt><dd>{{ $schedule->scheduled_on->format('M j, Y') }}</dd></div>
        <div><dt>Amount</dt><dd>{{ \App\Support\PhilippineCurrency::format($schedule->amount) }}</dd></div>
    </dl>
</section>
<x-staff.table label="Scheduled payouts" class="rounded-lg border bg-white">
    <table class="w-full text-left text-sm">
        <thead><tr><th scope="col">Beneficiary</th><th scope="col">Status</th><th scope="col" class="text-right">Action</th></tr></thead>
        <tbody>
            @forelse ($payouts as $payout)
                <tr><td>{{ $payout->seniorCitizen->full_name }}</td><td><x-badge :status="$payout->status" /></td><td class="text-right"><a class="secondary-button" href="{{ route('payouts.show', $payout) }}">View payout</a></td></tr>
            @empty
                <tr><td colspan="3" class="p-6 text-center">No payouts on this schedule.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-staff.table>
<div class="mt-4">{{ $payouts->links() }}</div>
@endsection
