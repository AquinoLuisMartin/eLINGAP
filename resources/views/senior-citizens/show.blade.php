@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl space-y-4" x-data="{
        tab: {{ \Illuminate\Support\Js::from(in_array(request('section'), ['profile', 'id-card', 'milestones', 'transfers'], true) ? request('section') : 'profile') }},
        tabs: ['profile', 'id-card', 'milestones', 'transfers'],
        selectTab(index) {
            this.tab = this.tabs[(index + this.tabs.length) % this.tabs.length];
            this.$nextTick(() => document.getElementById('tab-' + this.tab).focus());
        }
    }" @registration-verified.window="window.location.reload()">
        <header class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <x-record-return-link />
                @can('update', $seniorCitizen)
                    <a href="{{ route('senior-citizens.edit', [$seniorCitizen, ...request()->only(['from', 'search', 'search_by', 'stage', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page'])]) }}" class="primary-button shrink-0"><x-admin.icon name="pencil" size="16" /> Edit profile</a>
                @endcan
            </div>
            <div class="flex min-w-0 items-center gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-osca-primary/10 text-osca-primary dark:bg-blue-400/10 dark:text-blue-300" aria-hidden="true">
                        <x-admin.icon name="users" size="20" />
                    </div>
                    <div class="min-w-0 space-y-1">
                        <h2 class="break-words text-xl font-semibold sm:text-2xl">{{ $seniorCitizen->full_name }}</h2>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge :status="$seniorCitizen->status" />
                            <span class="text-sm text-slate-500">{{ implode(', ', array_filter([$seniorCitizen->barangay->name, $officeDetails['municipality']])) }}</span>
                        </div>
                    </div>
            </div>
        </header>

        <nav role="tablist" aria-label="Profile sections" class="flex w-fit max-w-full gap-1 overflow-x-auto rounded-lg border border-slate-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-900"
            @keydown.arrow-right.prevent="selectTab(tabs.indexOf(tab) + 1)" @keydown.arrow-left.prevent="selectTab(tabs.indexOf(tab) - 1)"
            @keydown.home.prevent="selectTab(0)" @keydown.end.prevent="selectTab(tabs.length - 1)">
            @foreach (['profile' => ['Profile', 'users'], 'id-card' => ['ID card', 'clipboard-list'], 'milestones' => ['Milestones & benefits', 'heart-pulse'], 'transfers' => ['Transfers', 'file-text']] as $key => [$label, $icon])
                <button type="button" id="tab-{{ $key }}" role="tab" aria-controls="{{ $key }}" :aria-selected="tab === '{{ $key }}'" :tabindex="tab === '{{ $key }}' ? 0 : -1" @click="tab = '{{ $key }}'"
                    class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-md px-3 py-2 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary"
                    :class="tab === '{{ $key }}' ? 'bg-osca-primary text-white' : 'text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'">
                    <x-admin.icon :name="$icon" size="17" /> {{ $label }}
                </button>
            @endforeach
        </nav>

        <div id="profile" role="tabpanel" aria-labelledby="tab-profile" tabindex="0" x-show="tab === 'profile'" class="space-y-4">
            <livewire:senior-citizens.requirements-review :senior-id="$seniorCitizen->id" />
            <div class="grid items-start gap-4 lg:grid-cols-3">
                <x-benefit-panel class="p-4! lg:col-span-2">
                    <div class="mb-4 border-b border-slate-200 pb-3 dark:border-slate-700">
                        <h3 class="text-base font-semibold">Profile</h3>
                    </div>
                    <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                        @foreach ([
                            'Registration number' => $seniorCitizen->registration_number,
                            'OSCA ID' => $seniorCitizen->osca_id_number ?: 'Not issued',
                            'Birth date' => $seniorCitizen->birth_date->format('F j, Y'),
                            'Age' => $seniorCitizen->birth_date->age.' years old',
                            'Sex' => ucfirst(strtolower($seniorCitizen->sex)),
                            'Barangay' => $seniorCitizen->barangay->name,
                            'Contact number' => $seniorCitizen->contact_number ?: 'Not provided',
                            'Address' => $seniorCitizen->address,
                        ] as $label => $value)
                            <div class="space-y-0.5">
                                <dt class="text-xs text-slate-500">{{ $label }}</dt>
                                <dd class="break-words text-sm font-medium text-slate-800 dark:text-slate-100">{{ $value }}</dd>
                            </div>
                        @endforeach
                        @if ($seniorCitizen->died_on)
                            <div class="space-y-1.5"><dt class="text-sm text-slate-500">Date of death</dt><dd class="text-sm text-slate-800 dark:text-slate-100">{{ $seniorCitizen->died_on->format('F j, Y') }}</dd></div>
                        @endif
                    </dl>
                </x-benefit-panel>
                <aside class="space-y-4" aria-label="Supporting information">
                    <x-benefit-panel class="p-4!">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-base font-semibold">Supporting Documents</h3>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600 dark:bg-slate-800">{{ $documents->count() }}</span>
                        </div>
                        <div class="space-y-3">
                            @forelse ($documents as $document)
                                <form method="GET" action="{{ route('senior-citizens.document', [$seniorCitizen, $document->id]) }}">
                                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg border border-slate-200 p-3 text-left transition-colors hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-osca-primary dark:border-slate-700 dark:hover:bg-slate-800">
                                        <span class="text-osca-primary dark:text-blue-300"><x-admin.icon name="file-text" size="20" /></span>
                                        <span class="min-w-0 flex-1"><span class="block break-words text-sm text-slate-800 dark:text-slate-100">{{ str_replace('_', ' ', ucfirst(strtolower($document->document_type))) }}</span><span class="block text-xs text-slate-500">{{ $document->created_at->format('M j, Y') }}</span></span>
                                        <span class="text-slate-500"><x-admin.icon name="arrow-up-right" size="16" /></span>
                                    </button>
                                </form>
                            @empty
                                <div class="flex items-center gap-2 rounded-lg bg-slate-50 p-3 dark:bg-slate-800">
                                    <span class="shrink-0 text-slate-400"><x-admin.icon name="file-text" size="18" /></span>
                                    <p class="text-sm text-slate-500">No supporting documents uploaded.</p>
                                </div>
                            @endforelse
                        </div>
                    </x-benefit-panel>
                    @if ($seniorCitizen->representative_name)
                        <x-benefit-panel class="p-4!">
                            <h3 class="mb-3 text-base font-semibold">Authorized Representative</h3>
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $seniorCitizen->representative_name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $seniorCitizen->representative_relationship }}</p>
                            <p class="mt-3 text-sm text-slate-800 dark:text-slate-100">{{ $seniorCitizen->representative_contact ?: 'No contact number provided' }}</p>
                        </x-benefit-panel>
                    @endif
                </aside>
            </div>
            <div class="grid items-start gap-4 lg:grid-cols-2">
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
            <x-benefit-panel class="p-4!">
                <details>
                    <summary class="cursor-pointer text-sm font-semibold">Record actions</summary>
                    <div class="mt-4 space-y-4">@include('senior-citizens._record-actions')</div>
                </details>
            </x-benefit-panel>
            </div>
        </div>

        <section id="id-card" role="tabpanel" aria-labelledby="tab-id-card" tabindex="0" x-show="tab === 'id-card'" x-cloak>
            <x-benefit-panel class="p-4!">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2"><h3 class="text-lg font-semibold">Digital ID details</h3><p class="max-w-xl text-sm text-slate-500">Review issuance details before preparing the official card. Attach a physical photo and collect the thumbprint after printing.</p></div>
                    @can('manageId', $seniorCitizen)
                        <a href="{{ route('senior-id.show', [$seniorCitizen, ...request()->only(['from', 'search', 'search_by', 'stage', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page'])]) }}" class="primary-button w-full lg:w-auto"><x-admin.icon name="clipboard-list" size="17" /> Prepare and print ID</a>
                    @endcan
                </div>
                <div class="mt-4 flex flex-col gap-4 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800 sm:flex-row sm:items-center">
                    <div class="flex size-24 shrink-0 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white p-2 text-center text-xs text-slate-500 dark:border-slate-600 dark:bg-slate-900">Physical 1 x 1 inch photo</div>
                    <div class="min-w-0 flex-1 space-y-4">
                        <div><p class="text-xs text-slate-500">{{ $officeDetails['office_name'] ?? 'Office not configured' }}</p><p class="mt-1 break-words text-xl font-semibold text-slate-800 dark:text-slate-100">{{ $seniorCitizen->full_name }}</p><p class="mt-1 text-sm text-slate-500">{{ implode(', ', array_filter([$seniorCitizen->barangay->name, $officeDetails['municipality'], $officeDetails['province']])) }}</p></div>
                        <dl class="grid gap-4 sm:grid-cols-2"><div><dt class="text-xs text-slate-500">OSCA ID</dt><dd class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-100">{{ $seniorCitizen->osca_id_number ?: 'Not issued' }}</dd></div><div><dt class="text-xs text-slate-500">Original issuance</dt><dd class="mt-1 text-sm text-slate-800 dark:text-slate-100">{{ $seniorCitizen->id_generated_at?->format('F j, Y') ?? 'Not recorded' }}</dd></div></dl>
                    </div>
                </div>
            </x-benefit-panel>
        </section>

        <div id="milestones" role="tabpanel" aria-labelledby="tab-milestones" tabindex="0" x-show="tab === 'milestones'" x-cloak class="space-y-4">
            <x-benefit-panel class="p-4!">
                <h3 class="text-lg font-semibold">Milestones and benefits</h3>
                <p class="mt-1 text-sm text-slate-500">Age milestones based on the recorded birth date. Eligibility is reviewed separately.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @forelse ($milestoneAges as $age)
                        @php($achieved = $seniorCitizen->birth_date->age >= $age)
                        <div class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                            <div class="flex items-center justify-between gap-2"><span class="text-2xl font-semibold text-osca-primary dark:text-blue-300">{{ $age }}+</span><span @class(['rounded-full px-2 py-1 text-xs', 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' => $achieved, 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' => !$achieved])>{{ $achieved ? 'Achieved' : 'Upcoming' }}</span></div>
                            <p class="flex items-center gap-2 text-sm text-slate-500"><x-admin.icon name="calendar-days" size="15" /> {{ $seniorCitizen->birth_date->copy()->addYears($age)->format('M j, Y') }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 sm:col-span-2 lg:col-span-4">No age milestones configured.</p>
                    @endforelse
                </div>
                <div class="mt-6 border-t border-slate-200 pt-5 dark:border-slate-700">
                    <h4 class="font-semibold">Historical program enrollments</h4>
                    <div class="mt-3 space-y-3">
                        @forelse ($seniorCitizen->beneficiaries as $beneficiary)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-3 dark:bg-slate-800"><span class="text-sm text-slate-800 dark:text-slate-100">{{ $beneficiary->program->name }}</span><x-badge :status="$beneficiary->status" /></div>
                        @empty
                            <p class="text-sm text-slate-500">OSCA Staff assign programs after registration and ID issuance.</p>
                        @endforelse
                    </div>
                </div>
            </x-benefit-panel>
            <div><h3 class="text-lg font-semibold">Registration and Benefits</h3><livewire:benefits.senior-record :senior-id="$seniorCitizen->id" /></div>
        </div>
        <section id="transfers" role="tabpanel" aria-labelledby="tab-transfers" tabindex="0" x-show="tab === 'transfers'" x-cloak class="space-y-4">
            @include('senior-citizens.transfers.panel')
        </section>
    </div>
@endsection
