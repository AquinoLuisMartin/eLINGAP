@props(['label', 'end' => false])
@php
    $hour = $end ? 'endHour' : 'hour';
    $minute = $end ? 'endMinute' : 'minute';
    $period = $end ? 'endPeriod' : 'period';
    $field = $end ? 'form.ends_at' : 'form.scheduled_at';
@endphp
<fieldset class="payout-time-input w-fit min-w-0 max-w-full" @input="update()" @change="update()">
    <legend class="mb-1.5 text-sm">{{ $label }}</legend>
    <div class="flex items-start gap-2">
        <label class="flex w-14 shrink-0 flex-col gap-1 text-xs"><input x-model="{{ $hour }}" @input.capture="restrictTimeInput($event.target, '{{ $hour }}', true)" type="text" inputmode="numeric" pattern="(0?[1-9]|1[0-2])" maxlength="2" placeholder="09" required data-validation-field="{{ $field }}" aria-label="{{ $label }} hour" @if ($end) x-ref="payoutEndHour" aria-describedby="payout-end-time-error" :aria-invalid="rangeError ? 'true' : null" @endif class="min-h-11 w-full rounded-lg border border-[var(--line)] bg-[var(--surface)] px-2 text-center text-base tabular-nums"><span data-field-label>Hour</span></label>
        <span class="inline-flex h-11 items-center text-xl" aria-hidden="true">:</span>
        <label class="flex w-14 shrink-0 flex-col gap-1 text-xs"><input x-model="{{ $minute }}" @input.capture="restrictTimeInput($event.target, '{{ $minute }}', false)" type="text" inputmode="numeric" pattern="[0-5][0-9]" maxlength="2" placeholder="00" required data-validation-field="{{ $field }}" aria-label="{{ $label }} minute" @if ($end) aria-describedby="payout-end-time-error" @endif class="min-h-11 w-full rounded-lg border border-[var(--line)] bg-[var(--surface)] px-2 text-center text-base tabular-nums"><span data-field-label>Minute</span></label>
        <div class="flex shrink-0 flex-col gap-1 text-xs">
            <div class="payout-period flex h-11 gap-1" role="group" aria-label="{{ $label }} AM or PM">
                @foreach (['AM', 'PM'] as $choice)
                    <button type="button" @click="{{ $period }} = '{{ $choice }}'; update()" :aria-pressed="{{ $period }} === '{{ $choice }}'" class="h-11 min-h-11 rounded-lg border border-[var(--line)] px-2 text-sm text-[var(--muted)] aria-pressed:border-osca-primary aria-pressed:bg-osca-primary aria-pressed:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">{{ $choice }}</button>
                @endforeach
            </div>
            <span>AM / PM</span>
        </div>
    </div>
</fieldset>
