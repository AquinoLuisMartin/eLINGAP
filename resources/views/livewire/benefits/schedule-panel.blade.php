<x-benefit-panel>
    <div class="flex flex-wrap justify-between gap-4"><div><p class="section-kicker">{{ $schedule->reference_number }} / {{ $schedule->status }}</p><h3 class="mt-2 text-xl font-semibold">{{ $schedule->program->name }}</h3><p class="mt-1 text-sm text-slate-500">{{ $schedule->scheduled_at->format('M j, Y g:i A') }} / {{ $schedule->location }}</p></div><div class="text-right text-sm"><p>{{ number_format($recipientTotal) }} recipients</p><p>Required: PHP {{ number_format((float) $requiredFunds, 2) }}</p><p>Available: PHP {{ number_format((float) $schedule->available_funds, 2) }}</p></div></div>
    @if (($schedule->rule_snapshot['government_level'] ?? $schedule->program->government_level) !== 'LGU')<p class="my-3 rounded bg-blue-50 p-3 text-sm text-blue-800 dark:bg-blue-950 dark:text-blue-200">External benefit tracking: confirm the provincial or national receipt rather than recording an LGU disbursement.</p>@endif
    @if ($notice)<p class="my-3 text-sm text-emerald-700 dark:text-emerald-300" role="status">{{ $notice }}</p>@endif
    <x-benefit-errors />
    @if ($schedule->preparation_token)
        <p wire:poll.10s class="my-3 text-sm" role="status">Preparing eligible recipients. Review and approval will be available when preparation finishes. If preparation stops, use Refresh eligibility list to retry.</p>
    @endif
    @can('manage', \App\Models\BenefitSchedule::class)
        @if ($schedule->status === 'DRAFT')
            <form wire:submit="fund" class="my-4 flex flex-wrap items-end gap-3">
                <label class="field-label">Amount for unfunded entitlements<input wire:model="amount" type="number" step="0.01" min="0.01"></label>
                <label class="field-label">Available funds<input wire:model="funds" type="number" step="0.01" min="0" required></label>
                <button class="secondary-button" wire:loading.attr="disabled">Update funding</button>
            </form>
            <label class="field-label">Review or exclusion note<textarea wire:model="reviewNote" rows="2" maxlength="1000" placeholder="Required when excluding a recipient"></textarea></label>
            <div class="my-4 flex flex-wrap gap-3">
                <button type="button" class="secondary-button" wire:click="regenerate" wire:loading.attr="disabled">Refresh eligibility list</button>
                <button type="button" class="primary-button" wire:click="approve" wire:confirm="Confirm every included recipient was checked and funds are sufficient?" wire:loading.attr="disabled" @disabled($schedule->preparation_token)>Approve reviewed list</button>
                <button type="button" class="secondary-button" wire:click="cancel" wire:confirm="Cancel this draft and keep all benefits pending?" wire:loading.attr="disabled">Cancel draft</button>
            </div>
        @elseif ($schedule->status === 'APPROVED')
            <div class="my-4 grid gap-3 md:grid-cols-2">
                <label class="field-label">Claimant<select wire:model="claimant"><option value="SELF">Senior personally</option>@if($schedule->allow_representative)<option value="REPRESENTATIVE">Authorized representative</option>@endif</select></label>
                @if (($schedule->rule_snapshot['government_level'] ?? $schedule->program->government_level) !== 'LGU')<label class="field-label">External receipt reference<input wire:model="externalReference" maxlength="180"></label>@endif
                @if ($schedule->requirements)<label class="text-sm"><input type="checkbox" wire:model="requirementsChecked"> Checked announced requirements: {{ $schedule->requirements }}</label>@endif
                @if ($schedule->rule_snapshot['groceries'] ?? false)<label class="text-sm"><input type="checkbox" wire:model="groceriesReceived"> Groceries received</label>@endif
                <label class="text-sm"><input type="checkbox" wire:model="paymentConfirmed"> I confirm this recipient received the benefit.</label>
            </div>
            <button type="button" class="secondary-button mb-3" wire:click="close" wire:confirm="Close this schedule and mark remaining unpaid benefits unclaimed?" wire:loading.attr="disabled">Close completed payout schedule</button>
        @endif
    @endcan
    <p wire:loading class="my-3 text-sm" role="status">Updating schedule...</p>
    <x-staff.table label="Schedule recipients" class="mt-4"><table><thead><tr><th scope="col">Senior / ID</th><th scope="col">Barangay</th><th scope="col">Original period</th><th scope="col">Amount</th><th scope="col">Status / Review</th><th scope="col">Action</th></tr></thead><tbody>
        @forelse ($entries as $entry)
            <tr wire:key="schedule-entry-{{ $entry->id }}"><td><a href="{{ route('senior-citizens.show', $entry->seniorCitizen) }}" class="text-button">{{ $entry->seniorCitizen->full_name }}</a><p class="text-xs text-slate-500">{{ $entry->seniorCitizen->osca_id_number }}</p></td><td>{{ $entry->seniorCitizen->barangay->name }}</td><td>{{ $entry->period_key }}</td><td>{{ $entry->amount === null ? 'Awaiting funds' : 'PHP '.number_format((float) $entry->amount, 2) }}</td><td>{{ $entry->status->label() }}<p class="text-xs text-slate-500">{{ $entry->reviewed_at ? 'Staff checked' : 'Needs staff review' }}</p><p class="text-xs text-slate-500">{{ $entry->seniorCitizen->status->value }}</p></td><td>
                @can('manage', \App\Models\BenefitSchedule::class)
                    @if ($schedule->status === 'DRAFT' && ! $schedule->preparation_token)<div class="flex gap-3"><button type="button" class="text-button" wire:click="review({{ $entry->id }}, true)" wire:loading.attr="disabled">Check and include</button><button type="button" class="text-button" wire:click="review({{ $entry->id }}, false)" wire:loading.attr="disabled">Exclude</button></div>
                    @elseif ($schedule->status === 'APPROVED' && $entry->status->value === 'UNPAID')<button type="button" class="primary-button" wire:click="claim({{ $entry->id }})" wire:confirm="Confirm receipt for this senior and generate a unique payment reference?" wire:loading.attr="disabled">{{ ($schedule->rule_snapshot['government_level'] ?? $schedule->program->government_level) === 'LGU' ? 'Confirm payment' : 'Record external receipt' }}</button>@endif
                @endcan
                @foreach ($entry->payments as $payment)<p wire:key="schedule-payment-{{ $payment->id }}" class="text-xs">{{ $payment->reference_number }} {{ $payment->reversed_at ? '(reversed)' : '' }}</p>@endforeach
            </td></tr>
        @empty
            <tr><td colspan="6">No recipients currently assigned. Existing claims, missing eligibility, and previously assigned entitlements are excluded from automatic preparation.</td></tr>
        @endforelse
    </tbody></table></x-staff.table>{{ $entries->links() }}
    @can('manage', \App\Models\BenefitSchedule::class)
        @if ($schedule->status === 'APPROVED')
            <details class="mt-6"><summary class="cursor-pointer font-semibold">Preview and approve payout SMS</summary>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="field-label">Audience<select wire:model="smsBarangay"><option value="">Entire scheduled batch</option>@foreach ($barangays as $barangay)<option wire:key="sms-barangay-{{ $barangay->id }}" value="{{ $barangay->id }}">{{ $barangay->name }}</option>@endforeach</select><span class="field-helper">Only eligible unpaid recipients in this schedule will be notified.</span></label>
                    <label class="field-label">SMS announcement<textarea wire:model="message" rows="10" maxlength="1600"></textarea><span class="field-helper">[PERIOD] and [AMOUNT] use each recipient's original entitlement.</span></label>
                    <button type="button" class="secondary-button" wire:click="previewSms" wire:loading.attr="disabled">Preview actual message and audience</button>
                    @if ($preview)
                        <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-700"><p class="text-sm">{{ $preview['eligible'] }} recipients / {{ $preview['excluded'] }} excluded</p><pre class="mt-3 whitespace-pre-wrap font-sans text-sm">{{ $preview['example'] ?: 'No eligible recipients with a valid SMS contact.' }}</pre></div>
                        <label class="text-sm"><input type="checkbox" wire:model="smsApproved"> I reviewed and approve this message and audience.</label>
                        <button type="button" class="primary-button" wire:click="sendSms" wire:loading.attr="disabled">Approve and queue SMS</button>
                    @endif
                </div>
            </details>
        @endif
    @endcan
</x-benefit-panel>
