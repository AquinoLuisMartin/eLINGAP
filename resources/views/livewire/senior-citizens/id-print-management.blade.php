<div>
    @if ($senior)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/50 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="manage-id-title" x-data="{}" x-trap.inert.noscroll="true" @keydown.escape.window="$wire.close()">
            <section class="max-h-[90dvh] w-full max-w-2xl overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
                <header class="flex items-start justify-between gap-3 border-b border-slate-200 p-4 dark:border-slate-700">
                    <h2 id="manage-id-title" class="text-xl font-semibold text-(--navy) dark:text-slate-100">Manage Senior Citizen ID</h2>
                    <button type="button" wire:click="close" class="icon-button" aria-label="Close ID management"><x-admin.icon name="x" size="20" /></button>
                </header>
                <div class="flex flex-col gap-4 p-4">
                    <dl class="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm sm:grid-cols-2 dark:border-slate-700 dark:bg-slate-800">
                        @foreach ([
                            'Senior Citizen Name' => $senior->full_name,
                            'OSCA ID Number' => $senior->osca_id_number ?: 'Not issued',
                            'ID Status' => $senior->lastConfirmedIdPrint ? 'Printed' : 'Pending Printing',
                            'Original Issuance Date' => $senior->id_generated_at?->format('M j, Y') ?? 'Not issued',
                            'Last Printed Date' => $senior->lastConfirmedIdPrint?->confirmed_at?->format('M j, Y g:i A') ?? 'Not confirmed',
                            'Printed By' => $senior->lastConfirmedIdPrint?->confirmedBy?->full_name ?? 'Not confirmed',
                            'Reprint Status' => match ($senior->latestIdReprintRequest?->status) { 'PENDING' => 'Pending Approval', 'APPROVED' => 'Approved', 'REJECTED' => 'Rejected', 'CONSUMED' => 'Completed', default => 'No request' },
                        ] as $label => $value)
                            <div wire:key="id-management-{{ $loop->index }}"><dt class="text-xs text-slate-500 dark:text-slate-400">{{ $label }}</dt><dd class="mt-1 wrap-anywhere font-medium text-(--navy) dark:text-slate-100">@if (in_array($label, ['ID Status', 'Reprint Status']))<x-badge :status="$value" />@else{{ $value }}@endif</dd></div>
                        @endforeach
                    </dl>
                    <div class="flex justify-end border-t border-slate-200 pt-4 dark:border-slate-700">
                        <button type="button" class="secondary-button" wire:click="close">Close</button>
                    </div>
                </div>
            </section>
        </div>
    @endif
</div>
