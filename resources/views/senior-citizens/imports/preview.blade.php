@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <section class="welcome-row"><div><p class="section-kicker">Senior registration</p><h2 class="text-2xl font-semibold">Review import</h2><p class="section-subtitle">{{ count($prepared['rows']) }} rows checked. {{ $prepared['ready'] }} ready, {{ $prepared['duplicates'] }} duplicates skipped, {{ $prepared['invalid'] }} invalid. Nothing has been saved yet.</p></div><a href="{{ route('registrations.import.create') }}" class="secondary-button">Upload another file</a></section>
    @if ($errors->any())<div role="alert" class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200"><ul class="list-inside list-disc">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
    <x-benefit-panel>
        <p class="mb-4 text-sm text-slate-500">Existing OSCA IDs are preserved. New records will be marked Needs review; staff must check identity, residency, historical IDs and representative proof before verification. A duplicate means the same full name, suffix and birth date already exists or repeats in this file. Saved records are never overwritten.</p>
        <div class="table-scroll"><table><caption class="sr-only">Normalized import preview with row validation results</caption><thead><tr><th scope="col">File row</th><th scope="col">Senior / birth date</th><th scope="col">Address / mobile</th><th scope="col">Existing OSCA ID</th><th scope="col">Result</th></tr></thead><tbody>
            @foreach ($rows as $row)
                <tr><td>{{ $row['row'] }}</td><td>{{ implode(' ', array_filter([$row['data']['first_name'] ?? '', $row['data']['middle_name'] ?? '', $row['data']['last_name'] ?? '', $row['data']['name_suffix'] ?? ''])) }}<p class="text-xs text-slate-500">{{ $row['data']['birth_date'] ?? '' }} / {{ $row['data']['sex'] ?? '' }} / {{ $row['data']['civil_status'] ?? '' }}</p>@if ($row['data']['applicant_type'] === 'REPRESENTATIVE')<p class="mt-2 text-xs">Representative: {{ $row['data']['representative_name'] ?? '' }} / {{ $row['data']['representative_relationship'] ?? '' }} / {{ $row['data']['representative_contact'] ?? '' }}</p>@endif</td><td>{{ $row['data']['address'] ?? '' }}<p class="text-xs text-slate-500">{{ $row['barangay'] }} / {{ $row['data']['contact_number'] ?? '' }}</p></td><td>{{ $row['data']['osca_id_number'] ?? 'None provided' }}</td><td>@if ($row['state'] === 'invalid')<span class="font-semibold text-red-700 dark:text-red-300">Needs correction</span><ul class="mt-1 list-inside list-disc text-sm">@foreach ($row['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>@elseif ($row['state'] === 'duplicate')Duplicate - skipped @else Ready for review @endif</td></tr>
            @endforeach
        </tbody></table></div>
        <div class="mt-4">{{ $rows->links() }}</div>
    </x-benefit-panel>
    <x-benefit-panel>
        @if ($prepared['invalid'])<p role="alert" class="mb-4 text-sm text-red-700 dark:text-red-300">Correct all invalid rows in your file, then upload it again. No rows can be imported until these errors are resolved.</p>@elseif (! $prepared['ready'])<p class="mb-4 text-sm text-slate-500">There are no new records to import. All rows are duplicates.</p>@else
            <form method="POST" action="{{ route('registrations.import.store', $token) }}" class="space-y-4" x-data="{ busy: false }" x-on:submit="busy = true" x-on:pageshow.window="busy = false">
                @csrf
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="reviewed" value="1" required><span>I checked the preview and existing OSCA IDs. Add these {{ $prepared['ready'] }} records for staff review and skip duplicates.</span></label>
                <button type="submit" class="primary-button disabled:opacity-50" x-bind:disabled="busy" x-text="busy ? 'Importing records...' : 'Confirm import'">Confirm import</button>
            </form>
        @endif
        <form method="POST" action="{{ route('registrations.import.destroy', $token) }}" class="mt-4">@csrf @method('DELETE')<button type="submit" class="secondary-button">Discard preview</button></form>
    </x-benefit-panel>
</div>
@endsection
