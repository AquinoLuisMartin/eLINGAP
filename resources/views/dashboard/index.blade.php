@extends('layouts.app')
@section('content')
<div class="mb-6"><p class="text-sm font-semibold uppercase text-osca-primary">Overview</p><h1 class="text-3xl font-bold">Staff dashboard</h1><p class="text-slate-600">Current registry and processing activity.</p></div>
<div class="grid gap-4 md:grid-cols-3">
    <a href="{{ route('senior-citizens.index', ['status' => 'VERIFIED']) }}" class="rounded-lg border bg-white p-5 hover:border-osca-primary"><p class="text-slate-600">Total active seniors</p><p class="text-3xl font-bold">{{ number_format($activeCount) }}</p></a>
    <a href="{{ route('applications.index', ['status' => 'APPROVED']) }}" class="rounded-lg border bg-white p-5 hover:border-osca-primary"><p class="text-slate-600">Approved applications</p><p class="text-3xl font-bold">{{ number_format($readyCount) }}</p></a>
    <div class="rounded-lg border bg-white p-5"><p class="text-slate-600">Upcoming 80, 90 and 100 milestones</p><p class="text-3xl font-bold">{{ number_format($milestoneCount) }}</p></div>
</div>
<div class="mt-6 grid gap-5 lg:grid-cols-2">
    <section class="rounded-lg border bg-white p-5"><h2 class="mb-4 text-xl font-semibold">Population by barangay</h2>@forelse ($barangays as $barangay)<div class="mb-3"><div class="flex justify-between text-sm"><span>{{ $barangay->name }}</span><span>{{ $barangay->total }}</span></div><div class="mt-1 h-3 rounded bg-slate-100"><div class="h-3 rounded bg-osca-primary" style="width: {{ $activeCount ? round(100 * $barangay->total / $activeCount) : 0 }}%"></div></div></div>@empty<p class="text-slate-600">No active seniors recorded yet.</p>@endforelse</section>
    <section class="rounded-lg border bg-white p-5"><h2 class="mb-4 text-xl font-semibold">Age distribution</h2>@foreach ($ageGroups as $bracket => $count)<div class="mb-3"><div class="flex justify-between text-sm"><span>{{ $bracket }}</span><span>{{ $count }}</span></div><div class="mt-1 h-3 rounded bg-slate-100"><div class="h-3 rounded bg-osca-primary" style="width: {{ $activeCount ? round(100 * $count / $activeCount) : 0 }}%"></div></div></div>@endforeach<p class="text-sm text-slate-600">Total: {{ number_format($ageGroups->sum()) }}</p></section>
    <section class="rounded-lg border bg-white p-5"><h2 class="text-xl font-semibold">Gender</h2>@forelse ($genders as $sex => $count)<p class="mt-2 flex justify-between"><span>{{ ucfirst(strtolower($sex)) }}</span><span>{{ $count }}</span></p>@empty<p class="mt-2 text-slate-600">No data yet.</p>@endforelse</section>
    <section class="rounded-lg border bg-white p-5"><h2 class="text-xl font-semibold">Registry status</h2>@forelse ($statuses as $status => $count)<p class="mt-2 flex justify-between"><x-badge :status="$status" /><span>{{ $count }}</span></p>@empty<p class="mt-2 text-slate-600">No data yet.</p>@endforelse</section>
</div>
@endsection
