@props(['name', 'label', 'required' => false, 'hint' => null])
<div {{ $attributes }}>
    <label for="{{ str_replace('.', '-', $name) }}" class="field-label">
        <span>{{ $label }} @if ($required)<span class="text-red-600" aria-hidden="true">*</span>@endif</span>
        {{ $slot }}
    </label>
    @if ($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
    @error($name)<p class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
</div>
