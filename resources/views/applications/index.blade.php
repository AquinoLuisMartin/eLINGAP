@extends('layouts.app')
@section('content')
<h1>Benefit applications</h1>
@include('components.alert')
<p><a href="{{ route('applications.create') }}">New application</a></p>
<table><thead><tr><th>Application</th><th>Applicant</th><th>Program</th><th>Status</th></tr></thead><tbody>
@forelse ($applications as $application)
<tr><td><a href="{{ route('applications.show', $application) }}">{{ $application->application_number }}</a></td><td>{{ $application->seniorCitizen->full_name }}</td><td>{{ $application->program->name }}</td><td>{{ $application->status->value }}</td></tr>
@empty
<tr><td colspan="4">No applications found.</td></tr>
@endforelse
</tbody></table>
{{ $applications->links() }}
@endsection
