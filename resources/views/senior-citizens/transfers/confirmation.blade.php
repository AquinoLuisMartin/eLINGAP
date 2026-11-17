<dialog x-ref="transferDialog" aria-labelledby="transfer-confirm-title" aria-describedby="transfer-confirm-description"
    @cancel="if (submitting) $event.preventDefault()"
    class="m-auto max-h-[85dvh] w-[calc(100%-2rem)] max-w-2xl overflow-hidden rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-slate-950/50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
    <div class="flex max-h-[85dvh] flex-col">
        <header class="shrink-0 border-b border-slate-200 px-5 py-5 sm:px-6 dark:border-slate-700">
            <h2 id="transfer-confirm-title" class="text-xl font-semibold tracking-tight">Confirm record transfer</h2>
            <p id="transfer-confirm-description" class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400">Are you sure you want to record this transfer? Please review the information before confirming.</p>
        </header>
        <div class="min-h-0 overflow-y-auto px-5 py-5 sm:px-6">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">Transfer type</dt><dd class="mt-1 font-medium" x-text="review?.type"></dd></div>
                <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">Transfer date</dt><dd class="mt-1 font-medium" x-text="review?.date"></dd></div>
                <div class="min-w-0 sm:col-span-2"><dt class="text-slate-500 dark:text-slate-400">Requested by</dt><dd class="mt-1 wrap-anywhere" x-text="review?.requester"></dd></div>
            </dl>
            <dl class="mt-5 divide-y divide-slate-200 rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-800/50">
                <div class="grid gap-1 py-3 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4"><dt class="text-slate-500 dark:text-slate-400">Current location</dt><dd class="wrap-anywhere leading-relaxed" x-text="review?.current"></dd></div>
            </dl>
            <section aria-labelledby="transfer-confirm-destination-title" class="mt-5">
                <h3 id="transfer-confirm-destination-title" class="border-b border-slate-200 pb-3 text-base font-semibold dark:border-slate-700">Destination Address</h3>
                <dl class="mt-4 grid gap-4 text-sm md:grid-cols-3">
                    <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">Municipality / City</dt><dd class="mt-1 wrap-anywhere" x-text="review?.destinationDetails.municipality"></dd></div>
                    <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">Province</dt><dd class="mt-1 wrap-anywhere" x-text="review?.destinationDetails.province"></dd></div>
                    <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">Barangay</dt><dd class="mt-1 wrap-anywhere" x-text="review?.destinationDetails.barangay"></dd></div>
                    <div class="min-w-0 md:col-span-3"><dt class="text-slate-500 dark:text-slate-400">House / Street / Purok / Sitio / Subdivision</dt><dd class="mt-1 wrap-anywhere font-medium" x-text="review?.destinationDetails.address"></dd></div>
                </dl>
            </section>
            <p x-show="!review?.external" class="mt-5 border-l-2 border-slate-300 pl-4 text-sm leading-relaxed text-slate-600 dark:border-slate-600 dark:text-slate-300">This transfer will update the senior citizen's registered address within the municipality. The senior citizen's local registration will remain active.</p>
            <p x-show="review?.external" class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm leading-relaxed text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">This transfer will cancel the senior citizen's local registration and stop new benefit assignments, ID printing, and QR validation. Existing records and transfer history will be retained.</p>
        </div>
        <footer class="flex shrink-0 flex-wrap justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-6 dark:border-slate-700 dark:bg-slate-800/50">
            <button type="button" autofocus class="secondary-button" :disabled="submitting" @click="cancelTransfer()">Cancel</button>
            <button type="button" class="primary-button" :disabled="submitting" @click="confirmTransfer()">Confirm transfer</button>
        </footer>
    </div>
</dialog>
