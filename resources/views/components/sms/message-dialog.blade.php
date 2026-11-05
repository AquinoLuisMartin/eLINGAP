@props(['broadcast' => false])
    <dialog id="sms-message-dialog" x-ref="messageDialog" x-on:close="selected = null" aria-labelledby="sms-message-title"
        class="m-auto max-h-[80dvh] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-xl border border-slate-200 bg-white p-6 text-slate-900 shadow-xl backdrop:bg-slate-950/50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
        <div class="flex items-start justify-between gap-4">
            <h2 id="sms-message-title" class="text-xl font-semibold">Message details</h2>
            <button type="button" class="secondary-button" autofocus x-on:click="$refs.messageDialog.close()">Close</button>
        </div>
        <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
            @if ($broadcast)<div><dt class="text-slate-500">OSCA ID</dt><dd class="mt-1" x-text="selected?.oscaId ?? ''"></dd></div>@endif
            <div><dt class="text-slate-500">{{ $broadcast ? 'Senior Name' : 'Recipient' }}</dt><dd class="mt-1 wrap-break-word" x-text="selected?.recipient ?? ''"></dd></div>
            <div><dt class="text-slate-500">Mobile number</dt><dd class="mt-1" x-text="selected?.mobile ?? ''"></dd></div>
            <div><dt class="text-slate-500">Queued</dt><dd class="mt-1" x-text="selected?.queued ?? ''"></dd></div>
            <div><dt class="text-slate-500">Sent</dt><dd class="mt-1" x-text="selected?.sent ?? ''"></dd></div>
            <div><dt class="text-slate-500">Status</dt><dd class="mt-1"><span class="rounded px-2 py-1 text-xs font-medium" :class="statusClass(selected?.status)" x-text="selected?.status ?? ''"></span></dd></div>
            <div x-show="selected?.status === 'Failed'" class="sm:col-span-2"><dt class="text-slate-500">Failure Reason</dt><dd class="mt-1 wrap-anywhere" x-text="selected?.failureReason ?? ''"></dd></div>
        </dl>
        <div class="mt-6 border-t border-slate-200 pt-5 dark:border-slate-700">
            <h3 class="text-sm font-semibold">Full message</h3>
            <p class="mt-3 whitespace-pre-wrap wrap-anywhere text-sm leading-relaxed" x-text="selected?.body ?? ''"></p>
        </div>
    </dialog>
