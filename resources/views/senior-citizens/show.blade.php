@extends('layouts.app')

@section('content')
    <a class="text-sm text-osca-primary underline" href="{{ route('senior-citizens.index') }}">Back to masterlist</a>
    <div class="my-5 flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-3xl font-semibold">{{ $seniorCitizen->full_name }}</h2><x-badge :status="$seniorCitizen->status" /></div>@can('update', $seniorCitizen)<a class="rounded bg-osca-primary px-4 py-2 text-white" href="{{ route('senior-citizens.edit', $seniorCitizen) }}">Edit profile</a>@endcan</div>
    <nav aria-label="Profile sections" class="mb-4 flex flex-wrap gap-3 text-sm"><a class="text-osca-primary underline" href="#profile">Profile</a><a class="text-osca-primary underline" href="#id-card">ID card</a><a class="text-osca-primary underline" href="#milestones">Milestones</a></nav>
    <section id="profile" class="rounded-lg border bg-white p-5"><h3 class="mb-4 text-xl font-semibold">Profile</h3><dl class="grid gap-4 sm:grid-cols-2"><div><dt class="text-sm text-slate-600">Registration number</dt><dd>{{ $seniorCitizen->registration_number }}</dd></div><div><dt class="text-sm text-slate-600">OSCA ID</dt><dd>{{ $seniorCitizen->osca_id_number ?: '—' }}</dd></div><div><dt class="text-sm text-slate-600">Birth date / age</dt><dd>{{ $seniorCitizen->birth_date->format('F j, Y') }} · {{ $seniorCitizen->birth_date->age }}</dd></div><div><dt class="text-sm text-slate-600">Sex</dt><dd>{{ ucfirst(strtolower($seniorCitizen->sex)) }}</dd></div><div><dt class="text-sm text-slate-600">Barangay</dt><dd>{{ $seniorCitizen->barangay->name }}</dd></div><div><dt class="text-sm text-slate-600">Contact</dt><dd>{{ $seniorCitizen->contact_number ?: '—' }}</dd></div><div><dt class="text-sm text-slate-600">Address</dt><dd>{{ $seniorCitizen->address }}</dd></div>@if ($seniorCitizen->died_on)<div><dt class="text-sm text-slate-600">Date of death</dt><dd>{{ $seniorCitizen->died_on->format('F j, Y') }}</dd></div>@endif</dl></section>
    <section id="id-card" class="mt-5 rounded-lg border bg-white p-5"><h3 class="mb-4 text-xl font-semibold">Digital ID details</h3>@if ($photo)<img src="{{ route('senior-citizens.photo', $seniorCitizen) }}" alt="ID photo of {{ $seniorCitizen->full_name }}" class="mb-3 size-28 rounded border object-cover">@else<p class="mb-3 text-sm text-slate-600">No ID photo uploaded.</p>@endif<p class="font-medium">{{ $seniorCitizen->full_name }}</p><p>OSCA ID: {{ $seniorCitizen->osca_id_number ?: '—' }}</p><p>{{ $seniorCitizen->barangay->name }}, Santa Maria, Bulacan</p></section>
    <section id="milestones" class="mt-5 rounded-lg border bg-white p-5"><h3 class="mb-4 text-xl font-semibold">Milestones and benefits</h3><div class="grid gap-3 sm:grid-cols-4">@foreach ($milestoneAges as $age)<div class="rounded border p-3"><strong>{{ $age }}+</strong><p class="text-sm">{{ $seniorCitizen->birth_date->copy()->addYears($age)->format('Y-m-d') }}</p><p class="text-sm">{{ $seniorCitizen->birth_date->age >= $age ? 'Achieved' : 'Upcoming' }}</p></div>@endforeach</div><h4 class="mt-5 font-semibold">Historical program enrollments</h4>@forelse ($seniorCitizen->beneficiaries as $beneficiary)<p>{{ $beneficiary->program->name }} · <x-badge :status="$beneficiary->status" /></p>@empty<p class="text-sm text-slate-600">Central registration covers applicable benefits; separate enrollment is unnecessary.</p>@endforelse</section>
    @can('declareDeceased', $seniorCitizen)
        @if ($seniorCitizen->status !== \App\Enums\SeniorCitizenStatus::Deceased)
            <details class="mt-6 rounded-lg border border-osca-danger bg-white p-5"><summary class="cursor-pointer font-semibold text-osca-danger">Declare deceased</summary><p class="my-3 text-sm">This declaration stops future releases and requires a death document. Confirm the record identifier before saving.</p><form method="POST" action="{{ route('senior-citizens.death.store', $seniorCitizen) }}" enctype="multipart/form-data" class="grid max-w-xl gap-3" onsubmit="return confirm('Declare this senior deceased?')">@csrf<label>Date of death <input type="date" name="died_on" required max="{{ today()->format('Y-m-d') }}" class="block w-full rounded border p-2"></label><label>Death certificate or supporting document <input type="file" name="death_document" accept=".pdf,.jpg,.jpeg,.png" required class="block w-full rounded border p-2"></label><label>Type {{ $seniorCitizen->osca_id_number ?: $seniorCitizen->registration_number }} <input name="confirmation" required autocomplete="off" class="block w-full rounded border p-2"></label><button class="rounded bg-osca-danger px-4 py-2 text-white">Record declaration</button></form></details>
        @endif
    @endcan
    @can('correctDeath', $seniorCitizen)
        @if ($seniorCitizen->status === \App\Enums\SeniorCitizenStatus::Deceased)<form method="POST" action="{{ route('senior-citizens.death.correct', $seniorCitizen) }}" class="mt-6 max-w-xl rounded-lg border p-5" onsubmit="return confirm('Correct this death declaration and return the record to pending verification?')">@csrf @method('PATCH')<label>Correction reason <textarea name="reason" required minlength="10" class="mt-1 block w-full rounded border p-2"></textarea></label><button class="mt-3 rounded bg-osca-primary px-4 py-2 text-white">Correct declaration</button></form>@endif
    @endcan
    @can('delete', $seniorCitizen)
        <form method="POST" action="{{ route('administration.senior-citizens.destroy', $seniorCitizen) }}" class="mt-6" onsubmit="return confirm('Archive this senior citizen record?')">
            @csrf @method('DELETE')
            <button type="submit" class="rounded border border-osca-danger px-4 py-2 text-osca-danger">Archive</button>
        </form>
    @endcan
    <x-benefit-panel class="mt-6"><h3 class="font-semibold">Supporting Documents</h3>@forelse ($documents as $document)<p><a class="text-button" href="{{ route('senior-citizens.document', [$seniorCitizen, $document->id]) }}">{{ $document->document_type }} / {{ $document->created_at->format('M j, Y') }}</a></p>@empty<p>No supporting documents uploaded.</p>@endforelse</x-benefit-panel>
    <h3 class="mt-7 text-xl font-semibold">Registration and Benefits</h3>
    <livewire:benefits.senior-record :senior-id="$seniorCitizen->id" />
    @if ($seniorCitizen->representative_name)
        <x-benefit-panel class="mt-6">
            <h3 class="font-semibold">Authorized Representative</h3>
            <p>{{ $seniorCitizen->representative_name }} / {{ $seniorCitizen->representative_relationship }}</p>
            <p>{{ $seniorCitizen->representative_contact }}</p>
        </x-benefit-panel>
    @endif
    <ul>
        @foreach ($seniorCitizen->histories as $history)
            <li>{{ $history->created_at->format('Y-m-d H:i') }}: {{ $history->action }}</li>
        @endforeach
    </ul>
@endsection
