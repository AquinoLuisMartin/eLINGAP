@props(['birthdays'])

<dialog id="birthday-greetings" x-ref="birthdayDialog" aria-labelledby="birthday-greetings-title"
    class="m-auto max-h-[90dvh] w-[calc(100%-2rem)] overflow-auto rounded-xl border border-slate-200 bg-white p-5 text-slate-900 shadow-xl backdrop:bg-slate-950/50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
    :class="draft ? 'max-w-lg' : 'max-w-5xl'"
    @cancel="if (busy) $event.preventDefault()" @close="clearHash()">
    <div class="mb-3.5 flex items-start justify-between gap-3">
        <div class="min-w-0 space-y-1">
            <h3 id="birthday-greetings-title" x-ref="greetingTitle" tabindex="-1" class="text-lg font-semibold" x-text="draft ? 'Birthday Greeting' : 'Birthdays'">Birthdays</h3>
            <p x-cloak x-show="draft" x-text="draft?.name" class="wrap-anywhere text-sm text-slate-600 dark:text-slate-300"></p>
        </div>
        <button type="button" class="min-h-8 shrink-0 rounded-md border border-slate-200 px-2.5 text-xs font-medium hover:bg-slate-100 disabled:opacity-50 dark:border-slate-700 dark:hover:bg-slate-800"
            @click="$el.closest('dialog').close()" :disabled="busy">Close</button>
    </div>

    <div x-show="!draft" class="grid gap-4">
        <div class="flex flex-wrap items-end gap-3">
            <label class="field-label mt-0 min-w-36 flex-1">Search birthdays
                <input type="search" x-ref="birthdaySearch" x-model="search" placeholder="Name or OSCA ID">
            </label>
            <label class="field-label mt-0 min-w-36 flex-1">Barangay
                <select x-model="barangay">
                    <option value="">All barangays</option>
                    @foreach ($birthdays->map(fn ($birthday) => ($birthday['senior'] ?? null)?->barangay?->name ?: 'No barangay recorded')->unique()->sort() as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
            </label>
            <x-button @click="sendToday()" x-bind:disabled="busy || !todayRecipients.length">
                <x-admin.icon name="message-square" :size="15" /> Send All Today's Greetings
            </x-button>
        </div>
        <p x-cloak x-show="notice" role="status" class="text-sm text-slate-600 dark:text-slate-300" x-text="notice"></p>
        <ul x-ref="birthdayList" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($birthdays as $birthday)
                <x-staff.birthday-alert :birthday="$birthday" in-list />
            @endforeach
        </ul>
        <p x-cloak x-show="!rows.some(row => matches(row))" class="text-sm text-slate-600 dark:text-slate-300">No matching birthdays.</p>
    </div>

    <div x-cloak x-show="draft" class="space-y-3.5">
        <p x-show="draft?.status" class="text-sm" x-text="statusLabel(draft?.status)"></p>
        <form id="birthday-greeting-form" x-ref="manualForm" x-show="draft && !draft.status" method="POST" action="{{ route('sms.messages.store') }}"
            class="space-y-3.5" @submit="if (busy) $event.preventDefault(); else busy = true"
            data-confirm-title="Send this SMS now?" data-confirm="The message will be submitted to the SMS provider when you confirm." data-confirm-label="Send SMS">
            @csrf
            <label class="grid gap-1.5 text-sm font-medium">
                <span>Contact or guardian mobile number <span aria-hidden="true">*</span></span>
                <input x-ref="greetingNumber" name="recipient_number" type="tel" inputmode="numeric" autocomplete="off" maxlength="12"
                    placeholder="9XX-XXX-XXXX" pattern="9[0-9]{2}-[0-9]{3}-[0-9]{4}" data-fixed-mobile required
                    class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-normal dark:border-slate-700 dark:bg-slate-800">
            </label>
            <label class="grid gap-1.5 text-sm font-medium">
                <span>Message <span aria-hidden="true">*</span></span>
                <textarea name="message" x-model="message" rows="3" maxlength="1600" required
                    class="w-full resize-y rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-normal dark:border-slate-700 dark:bg-slate-800"></textarea>
            </label>
        </form>
        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-3 dark:border-slate-800">
            <button type="button" class="text-button hover:underline" @click="openList()">Back to Birthdays</button>
            <x-button type="submit" form="birthday-greeting-form" x-show="!draft?.status" x-bind:disabled="busy">Send SMS</x-button>
            <a x-show="draft?.status" class="text-button hover:underline" href="{{ route('sms.messages.index') }}" :href="messageHistory()">View SMS messages</a>
        </div>
    </div>
</dialog>
