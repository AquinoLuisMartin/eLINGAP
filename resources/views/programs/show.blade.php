@extends('layouts.app')
@section('content')
<h1>{{ $program->name }}</h1>
@include('components.alert')
<p>{{ $program->agency }} | {{ $program->status }}</p>
<p>Budget: {{ number_format((float) $program->budget, 2) }}</p>
<p>{{ $program->description }}</p>
<p>Applications: {{ $program->applications_count }}</p>
@endsection
