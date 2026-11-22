<div>
    @if ($review)
        <div wire:key="cash-release-confirmation" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="confirm-cash-title" aria-describedby="confirm-cash-message" x-data="{}" x-trap.inert.noscroll="true" @keydown.escape.window="$wire.cancelReview()">
            <section class="w-full max-w-sm rounded-xl border border-slate-200 bg-white p-5 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                <h2 id="confirm-cash-title" class="text-lg font-semibold">Confirm Cash Release</h2>
                <p id="confirm-cash-message" class="mt-3 text-sm text-[var(--muted)]">Are you sure the cash has been handed to the verified claimant?</p>
                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <button type="button" wire:click="cancelReview" class="secondary-button" wire:loading.attr="disabled">Cancel</button>
                    <button type="button" wire:click="confirmClaim" class="primary-button" wire:loading.attr="disabled">Confirm Cash Release</button>
                </div>
            </section>
        </div>
    @elseif ($record)
        <div wire:key="payout-details-{{ $source }}-{{ $payoutId }}" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/50 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="payout-details-title" x-data="{}" x-trap.inert.noscroll="true" @keydown.escape.window="$wire.close()">
            <section class="max-h-[90dvh] w-full max-w-2xl overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
                <header class="flex items-center justify-between gap-3 border-b border-slate-200 p-4 dark:border-slate-700">
                    <h2 id="payout-details-title" class="text-lg font-semibold">Payout Details</h2>
                    <button type="button" wire:click="close" class="secondary-button" wire:loading.attr="disabled">Close</button>
                </header>
                @if ($message)
                    <div class="fixed top-5 right-5 z-[60] w-80 max-w-[calc(100vw-2.5rem)] rounded-lg border border-[var(--line)] bg-[var(--white)] p-4 shadow-lg" role="{{ $succeeded ? 'status' : 'alert' }}">
                        <p class="text-sm font-semibold">{{ $succeeded ? 'Claim saved' : 'Claim not saved' }}</p>
                        <p class="mt-1 text-sm">{{ $message }}</p>
                        <button type="button" wire:click="dismissMessage" class="text-button mt-3">Dismiss</button>
                    </div>
                @endif
                <div class="flex flex-col gap-4 p-4">
                    <h3 class="text-sm font-semibold">Beneficiary &amp; Payout Information</h3>
                    <dl class="grid gap-3 rounded-lg border border-[var(--line)] p-3 text-sm sm:grid-cols-2">
                        @foreach ([
                            'Beneficiary' => $record->seniorCitizen->surname_first_name,
                            'OSCA ID' => $record->seniorCitizen->osca_id_number ?: 'Not issued',
                            'Barangay' => $record->seniorCitizen->barangay->name,
                            'Program' => $isBenefit ? $record->program->name : $record->schedule->program->name,
                            'Payout period' => $isBenefit ? $record->period_key : $record->schedule->scheduled_on->format('M j, Y'),
                            'Approved amount' => $record->amount !== null ? \App\Support\PhilippineCurrency::format($review['reviewed_amount'] ?? $record->amount) : 'Not yet assigned',
                            'Payout status' => $isBenefit ? ($record->schedule?->status ?? 'Not scheduled') : $record->schedule->status,
                            'Claim status' => in_array($record->status->value, ['CLAIMED', 'RELEASED']) ? 'Claimed' : ucfirst(strtolower($record->status->value)),
                        ] as $label => $value)
                            <div wire:key="payout-detail-{{ $loop->index }}"><dt class="text-xs text-[var(--muted)]">{{ $label }}</dt><dd class="mt-1 wrap-anywhere font-medium">{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                    @if ($isBenefit && $canClaim)
                        @if ($claimFormOpen)
                            <form wire:submit="reviewClaim" class="grid gap-3">
                                <h3 class="text-sm font-semibold">Record Claim</h3>
                                <div class="field-label"><label data-select-label>Claim method</label><x-select label="Claim method" wire:model.live="form.claimant_type"><option value="SELF">Personal</option>@if ($record->schedule->allow_representative)<option value="REPRESENTATIVE">Proxy Claim</option>@endif</x-select></div>
                                @if ($form['claimant_type'] === 'REPRESENTATIVE')
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <label class="field-label">Representative name<input wire:model="form.claimant_name" maxlength="350" required></label>
                                        <label class="field-label">Relationship<input wire:model="form.claimant_relationship" maxlength="100" required></label>
                                        <label class="field-label sm:col-span-2">Representative contact (optional)<input wire:model="form.claimant_contact" maxlength="30"></label>
                                    </div>
                                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" wire:model="form.authorization_checked" required>I verified the representative's authorization.</label>
                                @endif
                                <label class="flex items-start gap-2 text-sm"><input type="checkbox" wire:model="form.identity_checked" required>I verified the claimant's identity.</label>
                                @if ($record->schedule->requirements)
                                    <p class="text-sm text-[var(--muted)]">Requirements: {{ $record->schedule->requirements }}</p>
                                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" wire:model="form.requirements_checked">I checked the announced requirements.</label>
                                @endif
                                @if ($record->rule_snapshot['groceries'] ?? false)
                                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" wire:model="form.groceries_received">The claimant received the groceries.</label>
                                @endif
                                <x-benefit-errors />
                                <div class="flex justify-end gap-2"><button type="button" class="secondary-button" wire:click="cancelClaim" wire:loading.attr="disabled">Cancel</button><button type="submit" class="primary-button" wire:loading.attr="disabled">Confirm Claim</button></div>
                            </form>
                        @else
                            <div class="flex justify-end"><button type="button" class="primary-button" wire:click="startClaim" wire:loading.attr="disabled">Record Claim</button></div>
                        @endif
                    @elseif (! in_array($record->status->value, ['CLAIMED', 'RELEASED']))
                        <div class="flex flex-col items-start gap-2">
                            <p class="text-xs text-[var(--muted)]">{{ in_array($record->status->value, ['CLAIMED', 'RELEASED']) ? 'This payout has already been claimed.' : 'Claim recording requires an approved, funded benefit schedule, staff review and a due payout date.' }}</p>
                        </div>
                    @endif
                    @if ($isBenefit && $record->payments->isNotEmpty())
                        <section class="border-t border-[var(--line)] pt-4" aria-label="Claim Information">
                            <div class="mb-3 flex items-center gap-3"><h3 class="text-sm font-semibold">Claim Information</h3>@if ($record->payments->whereNull('reversed_at')->isNotEmpty())<x-badge status="CLAIMED" />@endif</div>
                            @if ($record->payments->whereNull('reversed_at')->isEmpty())<p class="mb-3 text-sm text-[var(--muted)]">No claim has been recorded for this payout.</p>@endif
                            <x-payout-claim-history :payments="$record->payments" :show-demo-notice="false" />
                        </section>
                    @elseif (! $isBenefit && $record->released_at)
                        <div class="flex items-center gap-3"><h3 class="text-sm font-semibold">Claim Information</h3>@if ($record->status->value === 'RELEASED')<x-badge status="CLAIMED" />@endif</div>
                        @php
                            $legacyProxy = in_array($record->claimant_type, ['PROXY', 'REPRESENTATIVE'], true);
                            $legacyFields = [
                                'Amount released' => \App\Support\PhilippineCurrency::format($record->amount),
                                'Claim date/time' => $record->released_at->format('M j, Y g:i A'),
                                'Claimant' => $record->claimant_name ?: 'Not recorded',
                                'Claim method' => $legacyProxy ? 'Proxy' : ($record->claimant_type === 'SELF' ? 'Personal' : 'Not recorded'),
                            ];
                            if ($legacyProxy) {
                                $legacyFields['Relationship'] = $record->claimant_relationship ?: 'Not recorded';
                                $legacyFields['Contact number'] = $record->claimant_contact ?: 'Not recorded';
                            }
                            $legacyFields['Processing staff'] = $record->releasedBy?->full_name ?: 'Staff account unavailable';
                            $legacyFields['Identity verification'] = match ($record->osca_id_checked) { true => 'Verified', false => 'Not verified', default => 'Not recorded' };
                            if ($legacyProxy) {
                                $legacyFields['Authorization verification'] = match ($record->authorization_checked) { true => 'Verified', false => 'Not verified', default => 'Not recorded' };
                            }
                        @endphp
                        <dl class="grid gap-x-4 gap-y-3 rounded-lg border border-[var(--line)] p-3 text-sm sm:grid-cols-2">
                            @foreach ($legacyFields as $label => $value)
                                <div wire:key="legacy-claim-{{ $loop->index }}" class="min-w-0"><dt class="text-xs text-[var(--muted)]">{{ $label }}</dt><dd class="mt-1 wrap-anywhere font-semibold text-(--navy) dark:text-slate-100">{{ $value }}</dd></div>
                            @endforeach
                        </dl>
                    @else
                        <section class="border-t border-[var(--line)] pt-3">
                            <h3 class="text-sm font-semibold">Claim Information</h3>
                            <p class="mt-2 text-sm text-[var(--muted)]">No claim has been recorded for this payout.</p>
                        </section>
                    @endif
                </div>
            </section>
        </div>
    @endif
    @if ($message && ($succeeded || ! $record))
        <div wire:key="payout-claim-notification" class="fixed top-5 right-5 z-[60] w-80 max-w-[calc(100vw-2.5rem)] rounded-lg border border-[var(--line)] bg-[var(--white)] p-4 shadow-lg" role="{{ $succeeded ? 'status' : 'alert' }}" x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 3000)" x-show="visible" x-transition.opacity>
            <p class="text-sm">{{ $message }}</p>
        </div>
    @endif
</div>
