@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
    <h2 class="text-2xl font-semibold">Import preview unavailable</h2>
    <p class="text-sm text-slate-500">This preview expired, was discarded, or was already processed. Check registrations before uploading the file again.</p>
    <div class="flex flex-wrap gap-3"><a href="{{ route('registrations.import.create') }}" class="primary-button">Upload a file</a><a href="{{ route('registrations.index') }}" class="secondary-button">Back to registrations</a></div>
</div>
@endsection
