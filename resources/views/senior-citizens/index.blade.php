@extends('layouts.app')

@section('content')
    <h1>Senior citizens</h1>
    @include('components.alert')
    <p><a href="{{ route('senior-citizens.create') }}">Register senior citizen</a></p>
    <table>
        <thead><tr><th>Registration</th><th>Name</th><th>Barangay</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
            @forelse ($seniorCitizens as $seniorCitizen)
                <tr>
                    <td>{{ $seniorCitizen->registration_number }}</td>
                    <td>{{ $seniorCitizen->full_name }}</td>
                    <td>{{ $seniorCitizen->barangay->name }}</td>
                    <td>{{ $seniorCitizen->status->value }}</td>
                    <td><a href="{{ route('senior-citizens.show', $seniorCitizen) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="5">No senior citizen records found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $seniorCitizens->links() }}
@endsection
