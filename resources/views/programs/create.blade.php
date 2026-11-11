@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-staff.page-header title="Create program" description="Enter the program details and available budget.">
        <a class="secondary-button" href="{{ route('programs.index') }}">Back to programs</a>
    </x-staff.page-header>
    <form class="panel grid gap-5 sm:grid-cols-2" method="POST" action="{{ route('administration.programs.store') }}">
        @csrf
        <label class="ui-field">Name<input class="w-full" name="name" value="{{ old('name') }}" required maxlength="180"></label>
        <label class="ui-field">Agency<input class="w-full" name="agency" value="{{ old('agency') }}" required maxlength="180"></label>
        <label class="ui-field sm:col-span-2">Budget<input class="w-full" type="number" step="0.01" min="0" name="budget" value="{{ old('budget', 0) }}" required></label>
        <label class="ui-field">Starts on<input class="w-full" type="date" name="starts_on" value="{{ old('starts_on') }}"></label>
        <label class="ui-field">Ends on<input class="w-full" type="date" name="ends_on" value="{{ old('ends_on') }}"></label>
        <label class="ui-field sm:col-span-2">Description<textarea class="w-full" name="description" rows="4" maxlength="5000">{{ old('description') }}</textarea></label>
        <div class="staff-form-actions sm:col-span-2">
            <a class="secondary-button" href="{{ route('programs.index') }}">Cancel</a>
            <x-button type="submit">Create program</x-button>
        </div>
    </form>
</div>
@endsection
