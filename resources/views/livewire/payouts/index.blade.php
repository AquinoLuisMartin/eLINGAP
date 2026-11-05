<div>
    <x-workspace-navigation section="payouts" />
    <x-staff.page-header title="Payouts" description="Review beneficiaries, filter payout records, and record verified releases." />

    <dl class="staff-summary-grid grid-cols-2 lg:grid-cols-4" aria-label="Payout totals for current filters">
        @foreach (['Total payouts' => $counts->sum(), 'Pending / unclaimed' => ($counts['PENDING'] ?? 0) + ($counts['UNPAID'] ?? 0) + ($counts['UNCLAIMED'] ?? 0), 'Claimed' => ($counts['RELEASED'] ?? 0) + ($counts['CLAIMED'] ?? 0), 'Claimed via proxy' => $proxyCount] as $label => $count)
            <div wire:key="payout-count-{{ $loop->index }}" class="staff-summary-item">
                <dt>{{ $label }}</dt>
                <dd class="tabular-nums">{{ number_format($count) }}</dd>
            </div>
        @endforeach
    </dl>

    <section class="staff-filter-panel" aria-labelledby="payout-filters-title">
        <h3 id="payout-filters-title" class="mb-4 text-base font-semibold">Find payout records</h3>
        <form wire:submit.prevent="resetPage" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <label class="field-label">Search term
                <input type="search" name="search" wire:model.live.debounce.300ms="search" maxlength="100" placeholder="OSCA ID No. or name">
            </label>
            <div class="field-label">
                <label data-select-label>Status</label>
                <x-select label="Status" wire:model.live="status">
                    <option value="">All statuses</option>
                    @foreach (array_unique([...array_column(\App\Enums\PayoutStatus::cases(), 'value'), ...array_column(\App\Enums\BenefitStatus::cases(), 'value')]) as $statusOption)
                        <option wire:key="payout-status-{{ $statusOption }}" value="{{ $statusOption }}">{{ ucfirst(strtolower($statusOption)) }}</option>
                    @endforeach
                </x-select>
            </div>
            <div class="field-label">
                <label data-select-label>Program</label>
                <x-select label="Program" wire:model.live="programId" searchable>
                    <option value="">All programs</option>
                    @foreach ($programs as $program)
                        <option wire:key="payout-program-{{ $program->id }}" value="{{ $program->id }}">{{ $program->name }}</option>
                    @endforeach
                </x-select>
            </div>
            <div class="field-label">
                <label data-select-label>Barangay</label>
                <x-select label="Barangay" wire:model.live="barangayId" searchable>
                    <option value="">All barangays</option>
                    @foreach ($barangays as $barangay)
                        <option wire:key="payout-barangay-{{ $barangay->id }}" value="{{ $barangay->id }}">{{ $barangay->name }}</option>
                    @endforeach
                </x-select>
            </div>
            <div class="staff-form-actions sm:col-span-2 xl:col-span-4">
                <button type="button" wire:click="resetFilters" wire:loading.attr="disabled" class="secondary-button">Reset filters</button>
                <span wire:loading class="text-sm text-[var(--muted)]" role="status">Updating payouts...</span>
            </div>
        </form>
    </section>

    <section class="staff-results-panel" aria-labelledby="payout-results-title">
        <div class="staff-panel-heading">
            <h3 id="payout-results-title" class="scroll-mt-6">Payout records <span class="font-normal text-[var(--muted)]">({{ number_format($payouts->total()) }})</span></h3>
            <p>Pending recipients require staff review and an approved, funded schedule before payment.</p>
        </div>
        <x-staff.table label="Payout records" wire:loading.class="opacity-60" class="transition-opacity">
            <table>
                <thead>
                    <tr>
                        @foreach (['OSCA ID', 'Beneficiary', 'Barangay', 'Program', 'Amount', 'Claimant', 'Status', 'Action'] as $heading)
                            <th wire:key="payout-heading-{{ $loop->index }}" scope="col">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payouts as $payout)
                        <tr wire:key="payout-{{ $payout->source }}-{{ $payout->id }}">
                            <td class="tabular-nums">{{ $payout->seniorCitizen->osca_id_number ?: '-' }}</td>
                            <td><div class="min-w-44 max-w-64 whitespace-normal">{{ $payout->seniorCitizen->full_name }}</div></td>
                            <td><div class="min-w-32 max-w-48 whitespace-normal">{{ $payout->seniorCitizen->barangay->name }}</div></td>
                            <td>
                                <div class="min-w-48 max-w-72 whitespace-normal">
                                    {{ $payout->program->name }}
                                    @if ($payout->period_key)
                                        <span class="block text-xs text-[var(--muted)]">{{ $payout->period_key }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="tabular-nums">{{ $payout->amount !== null ? \App\Support\PhilippineCurrency::format($payout->amount) : 'Awaiting amount' }}</td>
                            <td><div class="max-w-48 whitespace-normal">{{ $payout->claimant_name ?: '-' }}</div></td>
                            <td><x-badge :status="$payout->status" /></td>
                            <td>
                                <a class="text-button" href="{{ $payout->url }}" aria-label="View payout for {{ $payout->seniorCitizen->full_name }}">View <x-admin.icon name="chevron-right" size="15" /></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><p class="py-6 text-center text-[var(--muted)]">No payouts match these filters. Adjust or reset your filters to try again.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-staff.table>
        @if ($payouts->hasPages())
            {{ $payouts->onEachSide(1)->links('components.pagination.livewire', ['label' => 'Payout records pagination', 'scrollTarget' => 'payout-results-title']) }}
        @endif
    </section>
</div>
