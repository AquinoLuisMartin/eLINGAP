@extends('layouts.app')

@section('content')
    <x-staff.page-header title="Edit senior citizen" description="Update the existing record and save your changes." />
    <form method="POST" action="{{ route('senior-citizens.update', [$seniorCitizen, ...request()->only(['from', 'search', 'search_by', 'stage', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page'])]) }}" enctype="multipart/form-data" class="grid max-w-3xl gap-4 rounded-lg border border-[var(--line)] bg-[var(--white)] p-5">
        @method('PUT')
        @include('senior-citizens._form')
    </form>
@endsection
