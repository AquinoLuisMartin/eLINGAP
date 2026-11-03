    @php
        $additionalFilters = filled($programId) || filled($benefit);
    @endphp

    <div class="registry-workspace flex min-w-0 flex-col gap-5">
        <x-staff.page-header class="mb-0" title="Senior Masterlist" description="Process senior citizen ID applications, review requirements, and prepare IDs.">
            @can('create', \App\Models\SeniorCitizen::class)
                <a href="{{ route('registrations.import.create') }}" class="secondary-button">Import records</a>
            @endcan
        </x-staff.page-header>

        <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-[var(--line)] bg-[var(--line)] md:grid-cols-4" aria-label="Registry totals for current filters">
            @foreach ($counts as $label => $count)
                <div wire:key="registry-count-{{ $loop->index }}" class="flex flex-col gap-1 bg-[var(--white)] px-5 py-4">
                    <dt class="text-sm text-[var(--muted)]">{{ $label === 'Total registry' ? 'Matching records' : $label }}</dt>
                    <dd class="text-2xl font-semibold tabular-nums text-[var(--ink)]">{{ number_format($count) }}</dd>
                </div>
            @endforeach
        </dl>

        <section class="rounded-lg border border-[var(--line)] bg-[var(--white)]" aria-labelledby="registry-filters-title">
            <div class="p-5">
                <h3 id="registry-filters-title" class="mb-4 text-base font-semibold">Find applications and records</h3>
                <div class="grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <label class="field-label">Search term
                        <input type="search" name="search" wire:model.live.debounce.300ms="search" data-uppercase-search class="uppercase placeholder:normal-case" maxlength="100" autocomplete="off" placeholder="OSCA ID No. or name">
                    </label>
                    <div class="min-w-0">
                        <div class="field-label"><label data-select-label>Barangay</label><x-select label="Barangay" name="barangay_id" wire:model.live="barangayId" searchable>
                            <option value="">All barangays</option>
                            @foreach ($barangays as $barangay)
                                <option wire:key="registry-barangay-{{ $barangay->id }}" value="{{ $barangay->id }}">{{ $barangay->name }}</option>
                            @endforeach
                        </x-select></div>
                    </div>
                    <div class="field-label"><label data-select-label>Age group</label><x-select label="Age group" name="age" wire:model.live="age">
                            <option value="">All ages</option>
                            @foreach (['60-69', '70-79', '80+'] as $ageOption)
                                <option wire:key="registry-age-{{ $ageOption }}" value="{{ $ageOption }}">{{ $ageOption }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div class="field-label"><label data-select-label>Registration status</label><x-select label="Registration status" name="status" wire:model.live="status">
                            <option value="">All statuses</option>
                            @foreach (\App\Enums\SeniorCitizenStatus::cases() as $statusOption)
                                <option wire:key="registry-status-{{ $statusOption->value }}" value="{{ $statusOption->value }}">{{ ucfirst(strtolower($statusOption->value)) }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div class="field-label"><label data-select-label>Sort by surname</label><x-select label="Sort by surname" name="name_order" wire:change="sortByName($event.target.value)">
                            <option value="" @selected($sort !== 'last_name')>Default order</option>
                            <option value="asc" @selected($sort === 'last_name' && $direction === 'asc')>A-Z</option>
                            <option value="desc" @selected($sort === 'last_name' && $direction === 'desc')>Z-A</option>
                        </x-select>
                    </div>
                </div>

                <details class="mt-2 border-t border-[var(--line)]" @if ($additionalFilters) open @endif>
                    <summary class="w-fit cursor-pointer rounded py-3 text-sm text-[var(--blue)] dark:text-blue-300">More filters</summary>
                    <div class="grid gap-4 pt-1 pb-4 sm:grid-cols-2">
                        <div class="field-label"><label data-select-label>Program or benefit</label><x-select label="Program or benefit" name="program_id" wire:model.live="programId">
                                <option value="">All programs</option>
                                @foreach ($programs as $program)
                                    <option wire:key="registry-program-{{ $program->id }}" value="{{ $program->id }}">{{ $program->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="field-label"><label data-select-label>Pension / assignment</label><x-select label="Pension / assignment" name="benefit" wire:model.live="benefit">
                                <option value="">All benefits</option>
                                @foreach (['PENSIONER' => 'Pensioner', 'NATIONAL_PENSION' => 'National pension', 'LOCAL_PENSION' => 'Local pension', 'NONE' => 'No assigned benefits'] as $value => $label)
                                    <option wire:key="registry-benefit-{{ $value }}" value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                </details>

                <div class="mt-1 flex flex-wrap items-center gap-3">
                    <button type="button" wire:click="resetFilters" class="secondary-button">Reset</button>
                </div>
            </div>
        </section>

        <section class="min-w-0 rounded-lg border border-[var(--line)] bg-[var(--white)]" aria-labelledby="registry-results-title">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--line)] px-5 py-4">
                <h3 id="registry-results-title" class="text-base font-semibold">Senior records</h3>
                <p class="text-sm text-[var(--muted)]" role="status">
                    @if ($seniorCitizens->total())
                        Showing {{ number_format($seniorCitizens->firstItem()) }}–{{ number_format($seniorCitizens->lastItem()) }} of {{ number_format($seniorCitizens->total()) }} results
                    @else
                        0 results
                    @endif
                </p>
            </div>

            <div wire:loading.class="opacity-50" class="table-scroll" role="region" aria-label="Senior records table" tabindex="0">
                <table class="w-full min-w-[56rem]" aria-label="Senior citizen masterlist">
                    <thead>
                        <tr>

                            @foreach (['OSCA ID No.', 'Last name', 'First name', 'Middle name', 'Age', 'Barangay', 'Programs and benefits', 'Actions'] as $heading)
                                <th wire:key="registry-heading-{{ $loop->index }}" scope="col">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($seniorCitizens as $seniorCitizen)
                            <tr wire:key="registry-record-{{ $seniorCitizen->id }}">

                                <td class="whitespace-nowrap tabular-nums">{{ $seniorCitizen->osca_id_number ?: 'Not issued' }}</td>
                                <td>{{ $seniorCitizen->last_name }}</td>
                                <td>{{ $seniorCitizen->first_name }}</td>
                                <td>{{ $seniorCitizen->middle_name ?: '—' }}</td>
                                <td><div class="flex flex-col items-start gap-2">{{ $seniorCitizen->birth_date->age }}<x-milestone-badge :senior="$seniorCitizen" /></div></td>
                                <td>{{ $seniorCitizen->barangay->name }}</td>
                                <td>
                                    @foreach ($seniorCitizen->activeAssignments as $assignment)<p wire:key="registry-assignment-{{ $assignment->id }}">{{ $assignment->program->name }}</p>@endforeach
                                    @if ($seniorCitizen->has_national_pension || $seniorCitizen->has_local_pension)<p>Pensioner ({{ implode(', ', array_filter([$seniorCitizen->has_national_pension ? 'National' : null, $seniorCitizen->has_local_pension ? 'Local' : null])) }})</p>
                                    @elseif ($seniorCitizen->activeAssignments->isEmpty())<span class="text-slate-500">None assigned</span>@endif
                                </td>
                                <td>
                                    <div class="flex flex-col items-start gap-2">
                                        @if ($seniorCitizen->status->value === 'PENDING' && auth()->user()->can('manageId', $seniorCitizen))
                                            <a class="text-button" href="{{ route('senior-id.show', [$seniorCitizen, 'from' => 'registry', ...$navigationFilters]) }}">Review requirements</a>
                                        @endif
                                        <a class="text-button" href="{{ route('senior-citizens.show', [$seniorCitizen, 'from' => 'registry', ...$navigationFilters]) }}" aria-label="View {{ $seniorCitizen->full_name }}">View record <x-admin.icon name="chevron-right" size="15" /></a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="p-6 text-center">No senior citizen records match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($seniorCitizens->hasPages())
                {{ $seniorCitizens->onEachSide(1)->links('components.pagination.livewire', ['label' => 'Senior masterlist pagination', 'scrollTarget' => 'registry-results-title']) }}
            @endif
        </section>
    </div>
