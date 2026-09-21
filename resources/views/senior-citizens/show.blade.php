@extends('layouts.app')

@section('content')
    <h1>{{ $seniorCitizen->full_name }}</h1>
    @include('components.alert')
    <dl>
        <dt>Registration number</dt><dd>{{ $seniorCitizen->registration_number }}</dd>
        <dt>OSCA ID</dt><dd>{{ $seniorCitizen->osca_id_number ?: 'Not assigned' }}</dd>
        <dt>Barangay</dt><dd>{{ $seniorCitizen->barangay->name }}</dd>
        <dt>Birth date</dt><dd>{{ $seniorCitizen->birth_date->format('F j, Y') }}</dd>
        <dt>Status</dt><dd>{{ $seniorCitizen->status->value }}</dd>
        <dt>Contact</dt><dd>{{ $seniorCitizen->contact_number ?: 'Not provided' }}</dd>
        <dt>Address</dt><dd>{{ $seniorCitizen->address }}</dd>
    </dl>
    <a href="{{ route('senior-citizens.edit', $seniorCitizen) }}">Edit</a>
    @can('delete', $seniorCitizen)
        <form method="POST" action="{{ route('administration.senior-citizens.destroy', $seniorCitizen) }}">
            @csrf @method('DELETE')
            <button type="submit">Archive</button>
        </form>
    @endcan
    <h2>History</h2>
    <ul>
        @foreach ($seniorCitizen->histories as $history)
            <li>{{ $history->created_at->format('Y-m-d H:i') }}: {{ $history->action }}</li>
        @endforeach
    </ul>
@endsection
