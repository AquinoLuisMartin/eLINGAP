@extends('layouts.app')

@section('content')
    <nav class="mb-6 flex flex-wrap gap-3" aria-label="Benefits navigation">
        <a href="{{ route('benefits.workspace') }}" class="secondary-button">Schedules and reports</a>
        <a href="{{ route('benefits.programs') }}" class="secondary-button">Program rules</a>
        @can('create', \App\Models\SmsMessage::class)<a href="{{ route('sms.blasts.create') }}" class="secondary-button">General announcement</a>@endcan
    </nav>
    @if (request()->routeIs('benefits.programs'))
        <livewire:benefits.programs />
    @else
        <livewire:benefits.workspace />
    @endif
@endsection
