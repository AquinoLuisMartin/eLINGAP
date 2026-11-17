<div class="flex min-w-0 flex-col gap-4">
    <div @class(['grid items-start gap-4', 'lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]' => $slots->has('milestones')])>
        <x-benefit-panel class="min-w-0 p-4!">
            <h2 class="text-lg font-semibold text-(--navy) dark:text-slate-100">Senior Eligibility &amp; Benefits</h2>
            @if ($notice)
                <p wire:key="benefit-notice-{{ md5($notice) }}" x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 5000)" x-show="visible" x-transition.opacity role="status" class="mt-2 text-xs text-emerald-700 dark:text-emerald-300">{{ $notice }}</p>
            @endif
            <x-benefit-errors />
            <dl class="my-3 grid gap-3 rounded-lg bg-slate-50 p-3 text-sm text-(--navy) sm:grid-cols-3 dark:bg-slate-800 dark:text-slate-100">
                <div><dt class="text-xs text-slate-500 dark:text-slate-400">Registration Status</dt><dd class="mt-1"><span @class(['inline-flex rounded px-2 py-1 text-xs font-medium', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' => $senior->status->value === 'VERIFIED', 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200' => $senior->status->value !== 'VERIFIED'])>{{ ucfirst(strtolower($senior->status->value)) }}</span></dd></div>
                <div><dt class="text-xs text-slate-500 dark:text-slate-400">Date Issued</dt><dd class="mt-1 font-medium">{{ $senior->id_generated_at?->format('M j, Y') ?? 'Not recorded' }}</dd></div>
                <div><dt class="text-xs text-slate-500 dark:text-slate-400">OSCA ID</dt><dd class="mt-1 wrap-anywhere font-medium">{{ $senior->osca_id_number ?? 'Not generated' }}</dd></div>
            </dl>
            <h3 class="text-sm font-semibold text-(--navy) dark:text-slate-100">Enrolled Programs</h3>
            <ul class="mt-2 flex flex-col gap-3 text-sm">
                @forelse ($senior->activeAssignments as $assignment)
                    <li wire:key="assigned-benefit-{{ $assignment->id }}" class="flex flex-col gap-1"><span class="font-semibold text-(--navy) dark:text-slate-100">{{ $assignment->program->name }}</span>@if($assignment->assigned_at)<span class="text-xs text-slate-500 dark:text-slate-400">Assigned {{ $assignment->assigned_at->format('M j, Y') }}</span>@endif</li>
                @empty
                    <li class="text-slate-500">No programs assigned.</li>
                @endforelse
            </ul>
            @can('manage', \App\Models\BenefitSchedule::class)
                @if ($senior->status->value === 'VERIFIED' && $senior->id_generated_at && $senior->osca_id_number)
                    <div class="mt-4">
                        <div class="flex min-w-0 flex-col gap-2"><label for="benefit-assignment-program" data-select-label class="text-sm font-medium text-slate-500 dark:text-slate-400">Program or Benefit</label><x-select id="benefit-assignment-program" label="Program or Benefit" wire:model="programId" required><option value="">Select program</option>@foreach ($programs as $program)<option wire:key="assignment-program-{{ $program->id }}" value="{{ $program->id }}">{{ $program->name }}</option>@endforeach</x-select></div>
                    </div>
                    <form wire:submit="reviewClassifications" class="mt-4 grid gap-2 border-t border-slate-200 pt-4 dark:border-slate-700">
                        <h3 class="text-base font-semibold text-(--navy) dark:text-slate-100">Senior Classifications</h3>
                        <div class="grid gap-x-4 sm:grid-cols-2">
                            @foreach ($classificationLabels as $field => $label)
                                <label wire:key="classification-{{ $field }}" class="flex min-h-11 cursor-pointer items-center gap-2 text-sm"><input type="checkbox" wire:model="classifications.{{ $field }}" class="shrink-0">{{ $label }}</label>
                            @endforeach
                        </div>
                        <div><button type="submit" class="primary-button w-full sm:w-auto" wire:loading.attr="disabled">Save Classifications</button></div>
                    </form>
                @else
                    <p class="mt-4 text-sm text-slate-500">Register, verify and generate the OSCA ID before assigning benefits or classifications.</p>
                @endif
            @endcan
        </x-benefit-panel>
        @if ($slots->has('milestones'))<aside class="min-w-0" aria-label="Age milestones">{{ $slots['milestones'] }}</aside>@endif
    </div>
    <x-benefit-panel class="min-w-0 p-4!">
        <h2 class="text-lg font-semibold text-(--navy) dark:text-slate-100">Benefit and Payment History</h2>
        <x-staff.table label="Senior benefit history" class="mt-3"><table><thead><tr><th scope="col">Program</th><th scope="col">Original period</th><th scope="col">Amount</th><th scope="col">Status</th><th scope="col">Payment references</th></tr></thead><tbody>
            @forelse ($entitlements as $entry)
                <tr wire:key="senior-entitlement-{{ $entry->id }}"><td>{{ $entry->program->name }}</td><td>{{ $entry->period_key }}</td><td>{{ $entry->amount === null ? 'Awaiting funds' : 'PHP '.number_format((float) $entry->amount, 2) }}</td><td>{{ $entry->status->label() }}</td><td>@foreach ($entry->payments as $payment)<p wire:key="senior-payment-{{ $payment->id }}">{{ $payment->reference_number }} {{ $payment->reversed_at ? '(reversed)' : '' }}</p>@endforeach</td></tr>
            @empty
                <tr><td colspan="5">No benefit entitlements prepared yet.</td></tr>
            @endforelse
        </tbody></table></x-staff.table>
        {{ $entitlements->links() }}
    </x-benefit-panel>
    @if ($classificationReview !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/50 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="confirm-classifications-title" x-data="{}" x-trap.inert.noscroll="true" @keydown.escape.window="$wire.cancelClassificationReview()">
            <section class="max-h-[90dvh] w-full max-w-lg overflow-y-auto rounded-xl border border-slate-200 bg-white p-4 shadow-lg sm:p-5 dark:border-slate-700 dark:bg-slate-900">
                <h2 id="confirm-classifications-title" class="text-lg font-semibold text-(--navy) dark:text-slate-100">Confirm Classifications</h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Are you sure you want to save the selected classifications for this senior citizen? Please verify the information before proceeding.</p>
                <x-benefit-errors />
                <h3 class="mt-4 text-sm font-semibold text-(--navy) dark:text-slate-100">Selected classifications</h3>
                @if (array_filter($classificationReview))
                    <ul class="mt-2 flex list-disc flex-col gap-2 pl-5 text-sm">
                        @foreach ($classificationLabels as $field => $label)
                            @if ($classificationReview[$field])<li wire:key="review-classification-{{ $field }}">{{ $label }}</li>@endif
                        @endforeach
                    </ul>
                @else
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">No classifications selected.</p>
                @endif
                <div class="mt-5 flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
                    <button type="button" class="secondary-button" wire:click="cancelClassificationReview" wire:loading.attr="disabled">Cancel</button>
                    <button type="button" class="primary-button" wire:click="saveClassifications" wire:loading.attr="disabled">Confirm &amp; Save</button>
                </div>
            </section>
        </div>
    @endif
</div>
