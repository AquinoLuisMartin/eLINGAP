@props(['birthday', 'inList' => false])

@php
    $senior = $birthday['senior'] ?? null;
    $id = (string) ($senior?->id ?? '');
    $name = $senior?->full_name ?: 'Senior citizen';
    $oscaId = $senior?->osca_id_number ?: 'No OSCA ID';
    $barangay = $senior?->barangay?->name ?: 'No barangay recorded';
    $days = $birthday['days'] ?? null;
    $date = $birthday['date'] ?? '';
    $status = ($birthday['status'] ?? null)?->value;
    $mobile = ($birthday['has_mobile'] ?? false) ? $senior?->contact_number : null;
    $composer = $mobile && $id && ! $status ? route('sms.messages.birthday', $id) : null;
@endphp

<li class="flex h-full min-w-0 flex-col justify-between gap-2.5 rounded-lg border border-slate-200 bg-slate-50 p-3.5 dark:border-slate-700 dark:bg-slate-800/60"
    @if ($inList)
        data-birthday-id="{{ $id }}" data-name="{{ $name }}" data-osca-id="{{ $oscaId }}" data-barangay="{{ $barangay }}"
        data-days="{{ $days }}" data-date="{{ $date }}" data-mobile="{{ $mobile }}" data-status="{{ $status }}"
        data-send-url="{{ $composer ? route('sms.messages.birthday.store', $id) : '' }}" x-show="matches($el.dataset)"
    @endif
>
    <div class="grid min-w-0 gap-1.5">
        <div class="flex flex-wrap items-center justify-between gap-1.5">
            <time datetime="{{ $date }}" @class([
                'rounded-md px-2 py-0.5 text-xs font-medium',
                'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200' => $days === 0,
                'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200' => $days !== 0,
            ])>{{ $days === 0 ? 'Today' : ($days === null ? 'Upcoming' : 'In '.$days.($days === 1 ? ' day' : ' days')) }}</time>
            @if (isset($birthday['age']))
                <span class="rounded-md bg-white px-2 py-0.5 text-xs text-slate-700 dark:bg-slate-900 dark:text-slate-200">Turning {{ $birthday['age'] }}</span>
            @endif
        </div>
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold" title="{{ $name }}">{{ $name }}</p>
            <p class="truncate text-xs text-slate-600 dark:text-slate-300" title="{{ $oscaId }} · {{ $barangay }}">{{ $oscaId }} &middot; {{ $barangay }}</p>
        </div>
        @if ($status || $composer)
            <p class="text-xs font-medium text-slate-600 dark:text-slate-300" @unless ($status) x-cloak x-show="queued[@js($id)]" @endunless>
                {{ match ($status) { 'SENT' => 'Greeting Sent', 'FAILED' => 'Greeting Failed', default => 'Greeting Queued' } }}
            </p>
        @endif
    </div>
    <div class="flex items-center justify-between gap-2">
        <span class="min-w-0 wrap-anywhere text-xs text-slate-600 tabular-nums dark:text-slate-300" x-text="mobileLabel(@js($mobile))">{{ $mobile ?? 'No mobile on file' }}</span>
        <a class="inline-flex shrink-0 items-center gap-1.5 rounded-md bg-blue-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-blue-700"
            href="{{ $composer ?? '#birthday-greetings' }}" aria-label="Send Greeting to {{ $name }}"
            @if (! $composer) aria-haspopup="dialog" aria-controls="birthday-greetings" @endif
            @click="if (busy || ! @js((bool) $composer) || queued[@js($id)]) { $event.preventDefault(); openGreeting(@js($id)) }">
            <x-admin.icon name="message-square" :size="14" /> Send Greeting
        </a>
    </div>
</li>
