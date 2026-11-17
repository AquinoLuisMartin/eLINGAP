<div>
    @if ($senior)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/50 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="request-id-reprint-title" x-data="{}" x-trap.inert.noscroll="true" @keydown.escape.window="$wire.close()">
            <section class="max-h-[90dvh] w-full max-w-2xl overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
                <header class="flex items-start justify-between gap-3 border-b border-slate-200 p-4 dark:border-slate-700">
                    <h2 id="request-id-reprint-title" class="text-xl font-semibold text-(--navy) dark:text-slate-100">Request Reprint Approval</h2>
                    <button type="button" wire:click="close" class="icon-button" aria-label="Close reprint request"><x-admin.icon name="x" size="20" /></button>
                </header>
                <form wire:submit="requestReprint" class="flex flex-col gap-4 p-4">
                    <p class="rounded-lg bg-slate-50 p-3 text-sm font-medium text-(--navy) dark:bg-slate-800 dark:text-slate-100">{{ $senior->full_name }} · {{ $senior->osca_id_number }}</p>
                    <x-registration-field name="reason" label="Reason for Reprint" required><x-select id="reason" wire:model="reason"><option value="">Select reason</option>@foreach ($reasons as $value => $label)<option wire:key="reprint-reason-{{ $value }}" value="{{ $value }}">{{ $label }}</option>@endforeach</x-select></x-registration-field>
                    <x-registration-field name="details" label="Additional Details (Optional)"><textarea id="details" wire:model="details" rows="3" maxlength="2000"></textarea></x-registration-field>
                    <div class="flex flex-wrap justify-end gap-3"><button type="button" class="secondary-button" wire:click="close">Cancel</button><button type="submit" class="primary-button" wire:loading.attr="disabled">Submit Request</button></div>
                </form>
            </section>
        </div>
    @endif
</div>
