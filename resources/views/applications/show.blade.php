@extends('layouts.app')
@section('content')
<h1>{{ $application->application_number }}</h1>
@include('components.alert')
<p>Applicant: {{ $application->seniorCitizen->full_name }}</p>
<p>Program: {{ $application->program->name }}</p>
<p>Status: {{ $application->status->value }}</p>
@can('review', $application)
@if ($application->status->value === 'PENDING')
<form method="POST" action="{{ route('applications.status.update', $application) }}">
    @csrf @method('PATCH')
    <label>Status <select name="status"><option value="APPROVED">Approve</option><option value="REJECTED">Reject</option></select></label>
    <label>Remarks <textarea name="remarks"></textarea></label>
    <button type="submit">Save decision</button>
</form>
@endif
@endcan
<h2>Status history</h2>
<ul>@foreach ($application->statusHistories as $history)<li>{{ $history->to_status }} - {{ $history->created_at->format('Y-m-d H:i') }}</li>@endforeach</ul>
@endsection
