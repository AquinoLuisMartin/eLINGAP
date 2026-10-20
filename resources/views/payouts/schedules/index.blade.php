@extends('layouts.app')
@section('content')
<x-workspace-navigation section="payouts" />
<x-staff.page-header title="Payout schedules" description="Review existing schedules or prepare a new program payout.">
    <a class="primary-button" href="{{ route('payout-schedules.create') }}">New schedule</a>
</x-staff.page-header>
<x-staff.table label="Payout schedules" class="rounded-lg border bg-white"><table class="w-full min-w-120 text-left text-sm"><thead class="bg-slate-100"><tr><th scope="col" class="p-3">Reference</th><th scope="col" class="p-3">Program</th><th scope="col" class="p-3">Date</th><th scope="col" class="p-3">Amount</th><th scope="col" class="p-3">Status</th></tr></thead><tbody class="divide-y">@forelse ($schedules as $schedule)<tr><td class="p-3"><a class="text-osca-primary underline" href="{{ route('payout-schedules.show', $schedule) }}">{{ $schedule->reference_number }}</a></td><td class="p-3">{{ $schedule->program->name }}</td><td class="p-3">{{ $schedule->scheduled_on->format('Y-m-d') }}</td><td class="p-3">{{ \App\Support\PhilippineCurrency::format($schedule->amount) }}</td><td class="p-3"><x-badge :status="$schedule->status" /></td></tr>@empty<tr><td colspan="5" class="p-6 text-center">No payout schedules yet.</td></tr>@endforelse</tbody></table></x-staff.table><div class="mt-4">{{ $schedules->links() }}</div>
@if ($benefitSchedules->isNotEmpty())
    <h3 class="mb-4 mt-8 text-xl font-semibold">Reviewed benefit schedules</h3>
    <x-staff.table label="Payout schedules" class=""><table><thead><tr><th scope="col">Reference</th><th scope="col">Program</th><th scope="col">Date and location</th><th scope="col">Status</th></tr></thead><tbody>
        @foreach ($benefitSchedules as $schedule)
            <tr><td><a class="text-button" href="{{ route('payout-schedules.create', ['program_id' => $schedule->program_id, 'schedule_id' => $schedule->id]) }}">{{ $schedule->reference_number }}</a></td><td>{{ $schedule->program->name }}</td><td>{{ $schedule->scheduled_at->format('M j, Y g:i A') }}<p class="text-sm text-slate-500">{{ $schedule->location }}</p></td><td><x-badge :status="$schedule->status" /></td></tr>
        @endforeach
    </tbody></table></x-staff.table>
    <div class="mt-4">{{ $benefitSchedules->links() }}</div>
@endif
@endsection
