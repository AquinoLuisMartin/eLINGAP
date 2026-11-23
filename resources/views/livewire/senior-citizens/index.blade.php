    @php
        $additionalFilters = filled($programId) || filled($benefit);
        $canResetFilters = $additionalFilters || filled($search) || filled($barangayId) || filled($age) || filled($status)
            || $searchBy !== 'all' || $sort !== 'id' || $direction !== 'desc';
    @endphp

    <div class="registry-workspace flex min-w-0 flex-col gap-5">
        <x-staff.page-header class="mb-0!" title="Senior Masterlist">
            @can('create', \App\Models\SeniorCitizen::class)
                <a href="{{ route('registrations.import.create') }}" class="secondary-button">Import records</a>
            @endcan
        </x-staff.page-header>

        <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-[var(--line)] bg-[var(--line)] md:grid-cols-4" aria-label="Registry totals for current filters">
            @foreach ($counts as $label => $count)
                <div wire:key="registry-count-{{ $loop->index }}" class="flex flex-col gap-1 bg-[var(--white)] px-4 py-3 sm:px-5">
                    <dt class="text-sm text-[var(--muted)]">{{ $label === 'Total registry' ? 'Matching records' : $label }}</dt>
                    <dd class="text-2xl leading-tight font-semibold tabular-nums text-[var(--ink)]">{{ number_format($count) }}</dd>
                </div>
            @endforeach
        </dl>

        <section class="staff-results-panel min-w-0 overflow-hidden rounded-lg border border-[var(--line)] bg-[var(--white)]" aria-labelledby="registry-results-title">
            <div class="staff-panel-heading flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b border-[var(--line)] px-4 py-4 sm:px-5">
                <h3 id="registry-results-title" class="text-base font-semibold">Senior records</h3>
                <p class="mt-0! text-sm text-[var(--muted)] tabular-nums" role="status">
                    @if ($seniorCitizens->total())
                        Showing {{ number_format($seniorCitizens->firstItem()) }}–{{ number_format($seniorCitizens->lastItem()) }} of {{ number_format($seniorCitizens->total()) }} results
                    @else
                        0 results
                    @endif
                </p>
            </div>

            <div class="border-b border-[var(--line)] px-4 pt-3 sm:px-5" role="group" aria-label="Senior record filters">
                <div class="grid grid-cols-2 items-end gap-3 xl:grid-cols-[minmax(0,1.5fr)_repeat(4,minmax(0,1fr))]">
                    <label class="field-label col-span-2 min-w-0 xl:col-span-1">Search
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
                    <div class="field-label min-w-0"><label data-select-label>Age group</label><x-select label="Age group" name="age" wire:model.live="age">
                            <option value="">All ages</option>
                            @foreach (['60-69', '70-79', '80+'] as $ageOption)
                                <option wire:key="registry-age-{{ $ageOption }}" value="{{ $ageOption }}">{{ $ageOption }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div class="field-label min-w-0"><label data-select-label>Registration status</label><x-select label="Registration status" name="status" wire:model.live="status">
                            <option value="">All statuses</option>
                            @foreach (\App\Enums\SeniorCitizenStatus::cases() as $statusOption)
                                <option wire:key="registry-status-{{ $statusOption->value }}" value="{{ $statusOption->value }}">{{ ucfirst(strtolower($statusOption->value)) }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div class="field-label min-w-0"><label data-select-label>Sort by surname</label><x-select label="Sort by surname" name="name_order" wire:change="sortByName($event.target.value)">
                            <option value="" @selected($sort !== 'last_name')>Default order</option>
                            <option value="asc" @selected($sort === 'last_name' && $direction === 'asc')>A-Z</option>
                            <option value="desc" @selected($sort === 'last_name' && $direction === 'desc')>Z-A</option>
                        </x-select>
                    </div>
                </div>

                <div class="mt-1 flex items-start gap-3">
                    <details class="group min-w-0 flex-1" @if ($additionalFilters) open @endif>
                        <summary class="flex min-h-11 list-none items-center gap-2 rounded text-sm text-[var(--blue)] hover:underline dark:text-blue-300 [&::-webkit-details-marker]:hidden">
                            <x-admin.icon name="chevron-down" size="15" class="shrink-0 group-open:rotate-180" />
                            Program and benefit filters
                        </summary>
                        <div class="grid gap-3 pt-1 pb-4 sm:grid-cols-2">
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

                    @if ($canResetFilters)
                        <button type="button" class="text-button min-h-11! shrink-0 hover:underline" wire:click="resetFilters" wire:loading.attr="disabled" wire:target="resetFilters">Reset filters</button>
                    @endif
                </div>
            </div>

            <x-staff.table label="Senior records table" wire:loading.class="opacity-60">
                <table class="w-full min-w-208! table-fixed [&_td]:px-3! [&_td]:py-2.5! [&_td]:align-middle! [&_th]:px-3! [&_th]:py-2.5! [&_th]:whitespace-normal!">
                    <thead>
                        <tr>
                            <th scope="col" class="w-27">OSCA ID No.</th>
                            <th scope="col" class="w-[13%]">Last name</th>
                            <th scope="col" class="w-[13%]">First name</th>
                            <th scope="col" class="w-[11%]">Middle name</th>
                            <th scope="col" class="w-13 text-center">Age</th>
                            <th scope="col" class="w-[15%]">Barangay</th>
                            <th scope="col">Programs and benefits</th>
                            <th scope="col" class="w-36">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($seniorCitizens as $seniorCitizen)
                            <tr class="focus-within:bg-[var(--paper)] hover:bg-[var(--paper)]" wire:key="registry-record-{{ $seniorCitizen->id }}">
                                <td class="whitespace-normal! wrap-anywhere tabular-nums">
                                    @if ($seniorCitizen->osca_id_number)
                                        {{ $seniorCitizen->osca_id_number }}
                                    @else
                                        <span class="text-[var(--muted)]">Not issued</span>
                                    @endif
                                </td>
                                <td class="whitespace-normal! wrap-anywhere">
                                    <span class="font-semibold">{{ $seniorCitizen->last_name }}</span>
                                    @if ($seniorCitizen->name_suffix)
                                        <span class="text-[var(--muted)]">{{ $seniorCitizen->name_suffix }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-normal! wrap-anywhere">{{ $seniorCitizen->first_name }}</td>
                                <td class="whitespace-normal! wrap-anywhere">{{ $seniorCitizen->middle_name ?: '—' }}</td>
                                <td class="text-center tabular-nums">{{ $seniorCitizen->birth_date->age }}</td>
                                <td class="whitespace-normal! wrap-anywhere">{{ $seniorCitizen->barangay->name }}</td>
                                <td class="whitespace-normal! wrap-anywhere">
                                    @if ($seniorCitizen->activeAssignments->count() > 1)
                                        <span class="font-medium">Program selection required</span>
                                    @elseif ($programName = $seniorCitizen->activeAssignments->first()?->program->name)
                                        {{ $programName }}
                                    @else
                                        <span class="text-[var(--muted)]">None assigned</span>
                                    @endif
                                </td>
                                <td class="whitespace-normal!">
                                    <div class="flex flex-col items-start">
                                        @if ($seniorCitizen->status->value === 'PENDING' && auth()->user()->can('manageId', $seniorCitizen))
                                            <a class="text-button min-h-7! hover:underline" href="{{ route('senior-id.show', [$seniorCitizen, 'from' => 'registry', ...$navigationFilters]) }}">Review requirements</a>
                                        @endif
                                        <a class="text-button min-h-7! font-semibold! hover:underline" href="{{ route('senior-citizens.show', [$seniorCitizen, 'from' => 'registry', ...$navigationFilters]) }}" aria-label="View {{ $seniorCitizen->full_name }}">View record <x-admin.icon name="chevron-right" size="14" /></a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><p class="py-3 text-[var(--muted)]">No senior citizen records match these filters.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-staff.table>
            @if ($seniorCitizens->hasPages())
                {{ $seniorCitizens->onEachSide(1)->links('components.pagination.livewire', ['label' => 'Senior masterlist pagination', 'scrollTarget' => 'registry-results-title']) }}
            @endif
        </section>
    </div>
