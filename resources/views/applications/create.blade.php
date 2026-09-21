@extends('layouts.app')
@section('content')
<h1>New benefit application</h1>
@include('components.alert')
<form method="POST" action="{{ route('applications.store') }}">
    @csrf
    <label>Senior citizen <select name="senior_citizen_id" required><option value="">Select senior citizen</option>@foreach ($seniorCitizens as $seniorCitizen)<option value="{{ $seniorCitizen->id }}">{{ $seniorCitizen->full_name }} ({{ $seniorCitizen->registration_number }})</option>@endforeach</select></label>
    <label>Program <select name="program_id" required><option value="">Select program</option>@foreach ($programs as $program)<option value="{{ $program->id }}">{{ $program->name }}</option>@endforeach</select></label>
    <label>Applied on <input type="date" name="applied_on" value="{{ old('applied_on', now()->toDateString()) }}" required></label>
    <label>Remarks <textarea name="remarks">{{ old('remarks') }}</textarea></label>
    <button type="submit">Submit application</button>
</form>
@endsection
