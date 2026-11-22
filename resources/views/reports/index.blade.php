@extends('layouts.app')

@section('content')
    <x-staff.page-header title="Reports" />
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach (\App\Services\Reports\StaffReport::TYPES as $type => $name)
            <article class="panel flex flex-col items-start gap-3">
                <h3 class="text-lg font-semibold">{{ $name }}</h3>
                <a href="{{ route('reports.workspace', $type) }}" class="secondary-button mt-auto">Open report</a>
            </article>
        @endforeach
    </div>
@endsection
