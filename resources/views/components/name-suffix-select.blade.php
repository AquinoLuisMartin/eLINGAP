@props(['value' => null])

@php($selected = \App\Enums\NameSuffix::normalize($value))

<x-select {{ $attributes }}>
    <option value="">No suffix</option>
    @foreach (\App\Enums\NameSuffix::cases() as $suffix)
        <option value="{{ $suffix->value }}" @selected($selected === $suffix->value)>{{ $suffix->value }}</option>
    @endforeach
</x-select>
