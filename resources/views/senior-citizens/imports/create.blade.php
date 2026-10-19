@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <section class="welcome-row"><div><p class="section-kicker">Senior registration</p><h2 class="text-2xl font-semibold">Import previous records</h2><p class="section-subtitle">Upload a spreadsheet, check the preview, then add records for staff review.</p></div><a href="{{ route('registrations.index') }}" class="secondary-button">Back to registrations</a></section>
    <x-benefit-panel>
        <h3 class="text-lg font-semibold">1. Prepare your file</h3>
        <p class="mt-2 text-sm text-slate-500">CSV (UTF-8), Excel XLSX or XLS. Maximum 5 MB and 500 records per file. Use one worksheet, with the column headers in the first row. Download a template and paste values only.</p>
        <div class="mt-4 flex flex-wrap gap-3">@foreach (['csv' => 'CSV template', 'xlsx' => 'Excel template', 'xls' => 'Excel 97-2003 template'] as $format => $label)<a href="{{ route('registrations.import.template', $format) }}" class="secondary-button">{{ $label }}</a>@endforeach</div>
        <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
            <div><dt class="font-semibold">Required columns</dt><dd class="mt-1 text-slate-500">first_name, last_name, birth_date, sex, barangay, address, contact_number</dd></div>
            <div><dt class="font-semibold">Dates and mobile numbers</dt><dd class="mt-1 text-slate-500">Use YYYY-MM-DD or MM/DD/YYYY dates. Excel date cells are also accepted. Mobile numbers may start with 09, 9 or +63 and are saved as +639XXXXXXXXX.</dd></div>
            <div><dt class="font-semibold">Historical OSCA IDs</dt><dd class="mt-1 text-slate-500">Use the optional osca_id_number column to preserve the existing ID. Format this column as Text to retain leading zeros. ID conflicts must be corrected before importing.</dd></div>
            <div><dt class="font-semibold">Consistent records</dt><dd class="mt-1 text-slate-500">Names, addresses and representative details are saved in uppercase. Sex accepts MALE / FEMALE / OTHER or M / F. Civil status accepts SINGLE / MARRIED / WIDOWED.</dd></div>
            <div><dt class="font-semibold">Representatives</dt><dd class="mt-1 text-slate-500">If provided, include representative_name, representative_relationship and representative_contact together. Upload and check authorization proof in the record's review workspace afterward.</dd></div>
            <div><dt class="font-semibold">Duplicates and review</dt><dd class="mt-1 text-slate-500">Existing identities and repeated rows are skipped without updating saved records. Invalid rows block the whole import. All new records require review before verification and ID issuance.</dd></div>
        </dl>
        <details class="mt-5 rounded-lg border border-slate-200 p-4 dark:border-slate-700"><summary class="cursor-pointer font-medium">Accepted barangay names</summary><ul class="mt-3 grid gap-2 text-sm sm:grid-cols-2">@foreach ($barangays as $barangay)<li>{{ $barangay->name }}</li>@endforeach</ul></details>
    </x-benefit-panel>
    <x-benefit-panel>
        <h3 class="text-lg font-semibold">2. Upload and preview</h3>
        <form method="POST" action="{{ route('registrations.import.preview') }}" enctype="multipart/form-data" class="mt-4 space-y-4" x-data="{ busy: false }" x-on:submit="busy = true" x-on:pageshow.window="busy = false">
            @csrf
            <label for="import-file" class="field-label">Spreadsheet file <span aria-hidden="true">*</span></label>
            <input id="import-file" type="file" name="file" accept=".csv,.xlsx,.xls" required aria-describedby="import-file-help" class="block w-full">
            <p id="import-file-help" class="text-sm text-slate-500">Previewing does not save senior records. The preview expires after 30 minutes.</p>
            @error('file')<p role="alert" class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
            <button type="submit" class="primary-button disabled:opacity-50" x-bind:disabled="busy" x-text="busy ? 'Preparing preview...' : 'Preview import'">Preview import</button>
        </form>
    </x-benefit-panel>
</div>
@endsection
