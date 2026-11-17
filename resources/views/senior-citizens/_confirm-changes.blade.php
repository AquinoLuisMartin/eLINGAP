<dialog x-ref="profileDialog" aria-labelledby="profile-confirm-title" aria-describedby="profile-confirm-description"
    @cancel="if (submitting) $event.preventDefault()"
    @keydown.escape.prevent.stop="cancelProfile()"
    class="m-auto max-h-[85dvh] w-[calc(100%-2rem)] max-w-3xl overflow-hidden rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-slate-950/50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
    <div class="flex max-h-[85dvh] flex-col">
        <header class="shrink-0 border-b border-slate-200 px-5 py-5 sm:px-6 dark:border-slate-700">
            <h2 id="profile-confirm-title" class="text-xl font-semibold tracking-tight">Confirm profile changes</h2>
            <p id="profile-confirm-description" class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400">Are you sure you want to save these changes to the senior citizen's profile? Please review the updated information before proceeding.</p>
        </header>
        <div class="min-h-0 overflow-y-auto px-5 py-5 sm:px-6">
            <p x-show="!changes.length" role="status" class="text-sm text-slate-500 dark:text-slate-400">No changes detected</p>
            <div x-show="changes.length" class="overflow-hidden rounded-lg border border-slate-200 text-sm dark:border-slate-700">
                <div aria-hidden="true" class="hidden grid-cols-[1fr_1.5fr_1.5fr] gap-4 bg-slate-50 px-4 py-3 font-medium text-slate-500 sm:grid dark:bg-slate-800 dark:text-slate-400"><span>Field</span><span>Previous value</span><span>Updated value</span></div>
                <dl class="divide-y divide-slate-200 dark:divide-slate-700">
                    <template x-for="change in changes" :key="change.field">
                        <div class="grid gap-3 px-4 py-4 sm:grid-cols-[1fr_1.5fr_1.5fr] sm:gap-4">
                            <dt class="font-medium" x-text="change.label"></dt>
                            <dd class="min-w-0 wrap-anywhere text-slate-500 dark:text-slate-400"><span class="mb-1 block text-xs sm:sr-only">Previous value: </span><span x-text="change.previous"></span></dd>
                            <dd class="min-w-0 wrap-anywhere font-medium"><span class="mb-1 block text-xs font-normal text-slate-500 sm:sr-only dark:text-slate-400">Updated value: </span><span x-text="change.updated"></span></dd>
                        </div>
                    </template>
                </dl>
            </div>
        </div>
        <footer class="flex shrink-0 flex-wrap justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-6 dark:border-slate-700 dark:bg-slate-800/50">
            <button type="button" autofocus class="secondary-button" :disabled="submitting" @click="cancelProfile()">Cancel</button>
            <button type="button" class="primary-button" :disabled="submitting || !changes.length" @click="confirmProfile()">Confirm changes</button>
        </footer>
    </div>
</dialog>
