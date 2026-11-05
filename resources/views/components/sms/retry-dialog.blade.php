@props(['broadcast' => false])
    <dialog id="sms-retry-dialog" x-ref="retryDialog" x-on:close="retry = null" aria-labelledby="sms-retry-title"
        class="m-auto max-h-[80dvh] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-xl border border-slate-200 bg-white p-6 text-slate-900 shadow-xl backdrop:bg-slate-950/50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
        @if ($broadcast)
            <h2 id="sms-retry-title" class="text-xl font-semibold">Resend Broadcast SMS</h2>
        @else
            <h2 id="sms-retry-title" class="text-xl font-semibold" x-text="'Retry sending SMS to ' + (retry?.recipient ?? '') + '?'"></h2>
        @endif
        <dl class="mt-5 grid gap-4 text-sm">
            @if ($broadcast)
                <div><dt class="text-slate-500">OSCA ID</dt><dd class="mt-1" x-text="retry?.oscaId ?? ''"></dd></div>
                <div><dt class="text-slate-500">Senior Name</dt><dd class="mt-1 wrap-anywhere" x-text="retry?.recipient ?? ''"></dd></div>
            @endif
            <div><dt class="text-slate-500">Mobile number</dt><dd class="mt-1" x-text="retry?.mobile ?? ''"></dd></div>
            <div><dt class="text-slate-500">Failure Reason</dt><dd class="mt-1 wrap-anywhere" x-text="retry?.failureReason ?? ''"></dd></div>
        </dl>
        <p class="mt-4 text-sm text-slate-500">This {{ $broadcast ? 'resend' : 'retry' }} updates the preview only. No SMS will be sent.</p>
        <div class="mt-6 flex flex-wrap justify-end gap-3">
            <button type="button" autofocus class="secondary-button" x-on:click="$refs.retryDialog.close()">Cancel</button>
            <button type="button" class="primary-button" x-on:click="confirmRetry()">{{ $broadcast ? 'Confirm Resend' : 'Confirm Retry' }}</button>
        </div>
    </dialog>
