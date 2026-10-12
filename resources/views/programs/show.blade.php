@extends('layouts.app')
@section('content')
<h2>{{ $program->name }}</h2>
@include('components.alert')
<p>{{ $program->agency }} | {{ $program->status }}</p>
<p>Budget: {{ number_format((float) $program->budget, 2) }}</p>
<p>{{ $program->description }}</p>
<p>Applications: {{ $program->applications_count }}</p>
@endsection
