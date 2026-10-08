@extends('layouts.app')
@section('content')
<div class="mb-6"><p class="text-sm font-semibold uppercase text-osca-primary">Data</p><h1 class="text-3xl font-bold">Reports</h1><p class="text-slate-600">Preview current records and export filtered CSV data.</p></div>
<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
    @foreach (\App\Services\Reports\StaffReport::TYPES as $type => $name)
        <article class="rounded-lg border bg-white p-5"><h2 class="text-lg font-semibold">{{ $name }}</h2><p class="mt-1 text-sm text-slate-600">Preview and CSV export</p><a href="{{ route('reports.workspace', $type) }}" class="mt-4 inline-block text-osca-primary underline">Open report</a></article>
    @endforeach
</div>
@endsection
