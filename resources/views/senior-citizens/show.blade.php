@extends('layouts.app')

@section('content')
    <div class="w-full min-w-0 max-w-full space-y-5" x-data="{
        tab: {{ \Illuminate\Support\Js::from(in_array(request('section'), ['profile', 'id-card', 'milestones', 'transfers'], true) ? request('section') : 'profile') }},
        tabs: ['profile', 'id-card', 'milestones', 'transfers'],
        selectTab(index) {
            this.tab = this.tabs[(index + this.tabs.length) % this.tabs.length];
            this.$nextTick(() => document.getElementById('tab-' + this.tab).focus());
        }
    }" @registration-verified.window="window.location.reload()">
        <header class="space-y-4">
            @php($adminRecord = auth()->user()->isAdmin())
            <a href="{{ $adminRecord ? route('administration.dashboard', ['tab' => 'records']) : route(request('from') === 'registrations' ? 'registrations.index' : 'senior-citizens.index', request()->only(['search', 'search_by', 'stage', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page'])) }}" class="text-button inline-flex min-h-11 items-center text-sm">&larr; Back to {{ $adminRecord ? 'registry' : (request('from') === 'registrations' ? 'registrations' : 'masterlist') }}</a>
            <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0 space-y-2">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Senior citizen record</p>
                    <h1 class="break-words text-2xl font-semibold sm:text-3xl">{{ $seniorCitizen->full_name }}</h1>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge :status="$seniorCitizen->status" />
                        <x-milestone-badge :senior="$seniorCitizen" />
                    </div>
                </div>
                @can('update', $seniorCitizen)
                    <a href="{{ route('senior-citizens.edit', [$seniorCitizen, ...request()->only(['from', 'search', 'search_by', 'stage', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page'])]) }}" class="primary-button shrink-0 self-start">Edit profile</a>
                @endcan
            </div>
        </header>

        <div class="border-b border-slate-200 dark:border-slate-700">
        <nav role="tablist" aria-label="Profile sections" class="grid w-full! grid-cols-2 gap-x-4 sm:flex sm:w-fit! sm:gap-7"
            @keydown.arrow-right.prevent="selectTab(tabs.indexOf(tab) + 1)" @keydown.arrow-left.prevent="selectTab(tabs.indexOf(tab) - 1)"
            @keydown.home.prevent="selectTab(0)" @keydown.end.prevent="selectTab(tabs.length - 1)">
            @foreach (['profile' => 'Profile', 'id-card' => 'ID card', 'milestones' => 'Milestones & benefits', 'transfers' => 'Transfers'] as $key => $label)
                <button type="button" id="tab-{{ $key }}" role="tab" aria-controls="{{ $key }}" :aria-selected="tab === '{{ $key }}'" :tabindex="tab === '{{ $key }}' ? 0 : -1" @click="tab = '{{ $key }}'"
                    class="inline-flex min-h-11 shrink-0 items-center justify-center border-b-2 px-1 py-3 text-sm transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-osca-primary dark:focus-visible:outline-blue-300"
                    :class="tab === '{{ $key }}' ? 'border-osca-primary text-osca-primary dark:border-blue-300 dark:text-blue-300' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800 dark:text-slate-400 dark:hover:border-slate-500 dark:hover:text-slate-100'">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
        </div>

        <div id="profile" role="tabpanel" aria-labelledby="tab-profile" tabindex="0" x-show="tab === 'profile'" x-cloak class="space-y-4">
            <livewire:senior-citizens.requirements-review :senior-id="$seniorCitizen->id" />
            <div class="grid items-start gap-4 lg:grid-cols-3">
                <div class="flex min-w-0 flex-col gap-4 lg:col-span-2">
                <x-benefit-panel class="p-5!">
                    <div class="mb-4 border-b border-slate-200 pb-3 dark:border-slate-700">
                        <h3 class="text-base font-semibold">Profile</h3>
                    </div>
                    <dl class="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                        @foreach ([
                            'OSCA ID No.' => $seniorCitizen->osca_id_number ?: 'Not issued',
                            'Birth date' => $seniorCitizen->birth_date->format('F j, Y'),
                            'Age' => $seniorCitizen->birth_date->age.' years old',
                            'Sex' => ucfirst(strtolower($seniorCitizen->sex)),
                            'Barangay' => $seniorCitizen->barangay->name,
                            'Contact number' => $seniorCitizen->contact_number ?: 'Not provided',
                            'Municipality' => $officeDetails['municipality'] ?: 'Not configured',
                            'Province' => $officeDetails['province'] ?: 'Not configured',
                            'House No. / Street / Purok / Sitio / Subdivision' => $seniorCitizen->address,
                        ] as $label => $value)
                            <div @class(['space-y-0.5 min-w-0', 'sm:col-span-2' => $label === 'House No. / Street / Purok / Sitio / Subdivision'])>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                                <dd class="break-words text-sm font-medium text-slate-800 dark:text-slate-100">{{ $value }}</dd>
                            </div>
                        @endforeach
                        @if ($seniorCitizen->died_on)
                            <div class="space-y-1.5"><dt class="text-sm text-slate-500">Date of death</dt><dd class="text-sm text-slate-800 dark:text-slate-100">{{ $seniorCitizen->died_on->format('F j, Y') }}</dd></div>
                        @endif
                    </dl>
                </x-benefit-panel>
                @if ($registrationApplicant['applicant_type'] === 'REPRESENTATIVE')
                    <x-benefit-panel class="p-4!">
                        <div class="mb-4 border-b border-slate-200 pb-3 dark:border-slate-700">
                            <h3 class="text-base font-semibold">Authorized Representative Details</h3>
                        </div>
                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                            @foreach ([
                                'Representative name' => $registrationApplicant['representative_name'],
                                'Relationship to senior' => $registrationApplicant['representative_relationship'],
                                'Contact number' => $registrationApplicant['representative_contact'],
                            ] as $label => $value)
                                <div class="space-y-0.5">
                                    <dt class="text-xs text-slate-500">{{ $label }}</dt>
                                    <dd class="break-words text-sm font-medium text-slate-800 dark:text-slate-100">{{ filled($value) ? $value : 'Not recorded' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </x-benefit-panel>
                @endif
            <x-benefit-panel class="p-4!">
                <details>
                    <summary class="cursor-pointer text-sm font-semibold">Record actions</summary>
                    <div class="mt-4 space-y-4">@include('senior-citizens._record-actions')</div>
                </details>
            </x-benefit-panel>
                </div>
                <aside class="space-y-4" aria-label="Supporting information">
                    <x-benefit-panel class="p-5!">
                        <div class="mb-4 flex items-center justify-between gap-3 border-b border-slate-200 pb-3 dark:border-slate-700">
                            <h3 class="text-base font-semibold">Supporting Documents</h3>
                            <span class="shrink-0 text-xs text-slate-500 dark:text-slate-400">{{ $documents->count() }} {{ \Illuminate\Support\Str::plural('file', $documents->count()) }}</span>
                        </div>
                        <div class="space-y-3">
                            @forelse ($documents as $document)
                                <a href="{{ route('senior-citizens.document', [$seniorCitizen, $document->id]) }}" target="_blank" rel="noopener" class="flex min-h-11 w-full items-center gap-3 rounded-md p-2 text-left transition-colors hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-osca-primary dark:hover:bg-slate-800 dark:focus-visible:outline-blue-300">
                                        <span class="min-w-0 flex-1"><span class="block break-words text-sm text-slate-800 dark:text-slate-100">{{ $document->idType?->name ?? str_replace('_', ' ', ucfirst(strtolower($document->document_type))) }}</span><span class="block text-xs text-slate-500">{{ $document->created_at->format('M j, Y') }}</span></span>
                                        <span class="text-sm text-osca-primary underline underline-offset-4 dark:text-blue-300">View<span class="sr-only"> {{ str_replace('_', ' ', strtolower($document->document_type)) }}</span></span>
                                </a>
                            @empty
                                <p class="text-sm leading-relaxed text-slate-500 dark:text-slate-400">No supporting documents uploaded.</p>
                            @endforelse
                        </div>
                    </x-benefit-panel>
            <x-benefit-panel class="p-4!">
                <details>
                    <summary class="cursor-pointer text-sm font-semibold">Record activity <span class="ml-2 text-xs font-normal text-slate-500">{{ $seniorCitizen->histories->count() }} entries</span></summary>
                    <ol class="mt-3 max-h-64 overflow-y-auto divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse ($seniorCitizen->histories as $history)
                            <li class="flex flex-col gap-1 py-3 sm:flex-row sm:justify-between sm:gap-4"><span class="text-sm text-slate-800 dark:text-slate-100">{{ ucfirst(str_replace('_', ' ', $history->action)) }}</span><time datetime="{{ $history->created_at->toIso8601String() }}" class="shrink-0 text-xs text-slate-500">{{ $history->created_at->format('M j, Y / g:i A') }}</time></li>
                        @empty
                            <li class="py-3 text-sm text-slate-500">No record activity yet.</li>
                        @endforelse
                    </ol>
                </details>
            </x-benefit-panel>
                </aside>
            </div>
        </div>

        <section id="id-card" role="tabpanel" aria-labelledby="tab-id-card" tabindex="0" x-show="tab === 'id-card'" x-cloak>
            <x-benefit-panel class="p-4!">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2"><h3 class="text-lg font-semibold">Digital ID details</h3><p class="max-w-xl text-sm text-slate-500">Attach the photo and collect the thumbprint after printing.</p></div>
                    @can('manageId', $seniorCitizen)
                        <a href="{{ route('senior-id.show', [$seniorCitizen, ...request()->only(['from', 'search', 'search_by', 'stage', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page'])]) }}" class="primary-button w-full lg:w-auto">Prepare and print ID</a>
                    @endcan
                </div>
                <div class="mt-4 flex flex-col gap-4 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800 sm:flex-row sm:items-center">
                    <div class="flex size-24 shrink-0 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white p-2 text-center text-xs text-slate-500 dark:border-slate-600 dark:bg-slate-900">Physical 1 x 1 inch photo</div>
                    <div class="min-w-0 flex-1 space-y-4">
                        <div><p class="text-xs text-slate-500">{{ $officeDetails['office_name'] ?? 'Office of the Senior Citizen Affairs' }}</p><p class="mt-1 break-words text-xl font-semibold text-slate-800 dark:text-slate-100">{{ $seniorCitizen->full_name }}</p><p class="mt-1 text-sm text-slate-500">{{ implode(', ', array_filter([$seniorCitizen->barangay->name, $officeDetails['municipality'], $officeDetails['province']])) }}</p></div>
                        <dl class="grid gap-4 sm:grid-cols-2"><div><dt class="text-xs text-slate-500">OSCA ID</dt><dd class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-100">{{ $seniorCitizen->osca_id_number ?: 'Not issued' }}</dd></div><div><dt class="text-xs text-slate-500">Original issuance</dt><dd class="mt-1 text-sm text-slate-800 dark:text-slate-100">{{ $seniorCitizen->id_generated_at?->format('F j, Y') ?? 'Not recorded' }}</dd></div></dl>
                    </div>
                </div>
            </x-benefit-panel>
        </section>

        <div id="milestones" role="tabpanel" aria-labelledby="tab-milestones" tabindex="0" x-show="tab === 'milestones'" x-cloak class="space-y-4">
            <livewire:benefits.senior-record :senior-id="$seniorCitizen->id">
                <livewire:slot name="milestones">
                    <x-benefit-panel class="p-4!">
                        <h3 class="text-lg font-semibold text-(--navy) dark:text-slate-100">Age Milestones</h3>

                        <ul class="mt-3 flex flex-col gap-2">
                            @forelse ($milestoneAges as $age)
                                @php($achieved = $seniorCitizen->birth_date->age >= $age)
                                <li class="flex flex-col gap-1 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800">
                                    <div class="flex items-start justify-between gap-3"><span class="text-2xl font-semibold text-osca-primary dark:text-blue-300">{{ $age }}+</span><span class="ui-label shrink-0">{{ $achieved ? 'Achieved' : 'Upcoming' }}</span></div>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $seniorCitizen->birth_date->copy()->addYears($age)->format('M j, Y') }}</p>
                                </li>
                            @empty
                                <li class="text-sm text-slate-500">No age milestones configured.</li>
                            @endforelse
                        </ul>
                    </x-benefit-panel>
                </livewire:slot>
            </livewire:benefits.senior-record>
        </div>
        <section id="transfers" role="tabpanel" aria-labelledby="tab-transfers" tabindex="0" x-show="tab === 'transfers'" x-cloak class="space-y-4">
            @include('senior-citizens.transfers.panel')
        </section>
    </div>
@endsection
