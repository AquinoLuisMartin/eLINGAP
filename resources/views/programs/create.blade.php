@extends('layouts.app')
@section('content')
<h1>Create program</h1>
@include('components.alert')
<form method="POST" action="{{ route('administration.programs.store') }}">
    @csrf
    <label>Name <input name="name" value="{{ old('name') }}" required></label>
    <label>Agency <input name="agency" value="{{ old('agency') }}" required></label>
    <label>Budget <input type="number" step="0.01" min="0" name="budget" value="{{ old('budget', 0) }}" required></label>
    <label>Starts on <input type="date" name="starts_on" value="{{ old('starts_on') }}"></label>
    <label>Ends on <input type="date" name="ends_on" value="{{ old('ends_on') }}"></label>
    <label>Description <textarea name="description">{{ old('description') }}</textarea></label>
    <button type="submit">Create program</button>
</form>
@endsection
