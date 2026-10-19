@props(['value' => ''])
@php
    $model = $attributes->wire('model')->value();
    $value = is_string($value) ? $value : '';
    $number = \App\Services\SeniorCitizens\SeniorRegistrationInput::normalize(['contact_number' => $value])['contact_number'];
    $display = preg_replace('/^\+63(9[0-9]{2})([0-9]{3})([0-9]{4})$/', '$1-$2-$3', $number);
@endphp
<span class="senior-mobile-field mt-1 flex w-full items-stretch" x-data="{ number: {{ $model ? '$wire.entangle('.\Illuminate\Support\Js::from($model).')' : \Illuminate\Support\Js::from($display) }} }" x-init="number = window.formatSeniorMobile(number)">
    <span class="inline-flex shrink-0 items-center rounded-l border border-r-0 border-slate-300 bg-slate-100 px-3 text-sm text-slate-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400" aria-hidden="true">+63</span>
    <input {{ $attributes->whereDoesntStartWith('wire:model')->class(['min-w-0 w-full rounded-l-none rounded-r border p-2']) }} value="{{ $display }}" type="tel" inputmode="tel" autocomplete="tel-national" placeholder="967-234-8976" pattern="9[0-9]{2}-[0-9]{3}-[0-9]{4}" maxlength="24" x-model="number" x-on:input="number = window.formatSeniorMobile($event.target.value)" aria-description="Philippine mobile number. Country code +63 is already provided. Enter 10 digits starting with 9.">
</span>
