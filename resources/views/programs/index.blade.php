@extends('layouts.app')
@section('content')
<h1>Programs</h1>
@include('components.alert')
@can('create', App\Models\Program::class)<p><a href="{{ route('administration.programs.create') }}">Create program</a></p>@endcan
<ul>
    @forelse ($programs as $program)
        <li><a href="{{ route('programs.show', $program) }}">{{ $program->name }}</a> - {{ $program->status }}</li>
    @empty
        <li>No programs found.</li>
    @endforelse
</ul>
{{ $programs->links() }}
@endsection
