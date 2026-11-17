@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <a href="{{ route('senior-citizens.show', [$seniorCitizen, ...request()->only(['from', 'search', 'search_by', 'stage', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page'])]) }}" x-on:click="if (window.history.length > 1) { $event.preventDefault(); window.history.back(); }" class="text-button text-sm">&larr; Back</a>
    </div>
    <x-staff.page-header title="Edit senior citizen" description="Update the existing record and save your changes." />
    @php($profileOriginal = [...$seniorCitizen->only(['barangay_id', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'sex', 'civil_status', 'contact_number', 'address']), 'birth_date' => $seniorCitizen->birth_date?->format('Y-m-d')])
    <form method="POST" action="{{ route('senior-citizens.update', [$seniorCitizen, ...request()->only(['from', 'search', 'search_by', 'stage', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page'])]) }}" enctype="multipart/form-data" x-ref="profileForm" x-data="profileConfirmation({{ \Illuminate\Support\Js::from(['original' => $profileOriginal, 'barangays' => $barangays->pluck('name', 'id')]) }})" @submit="reviewProfile($event)" @pageshow.window="resetProfileSubmission()" class="grid w-full max-w-full grid-cols-1 gap-x-6 gap-y-5 rounded-lg border border-[var(--line)] bg-[var(--white)] p-5 md:grid-cols-2">
        @method('PUT')
        @include('senior-citizens._form')
        @include('senior-citizens._confirm-changes')
    </form>
@endsection
