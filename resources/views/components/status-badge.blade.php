@props(['status'])
@php
    $styles = ['Active' => 'bg-emerald-50 text-emerald-700 border border-emerald-100', 'Relocated' => 'bg-amber-50 text-amber-700 border border-amber-100', 'Confined' => 'bg-purple-50 text-purple-700 border border-purple-100', 'Deceased' => 'bg-red-50 text-red-700 border border-red-100', 'Bedridden' => 'bg-amber-50 text-amber-700 border border-amber-100'];
    $dots = ['Active' => 'bg-emerald-500', 'Relocated' => 'bg-amber-500', 'Confined' => 'bg-purple-500', 'Deceased' => 'bg-red-500', 'Bedridden' => 'bg-amber-500'];
@endphp
<span data-profile-status class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $styles[$status] ?? 'bg-gray-100 text-gray-600 border border-gray-200' }}"><span class="h-1.5 w-1.5 rounded-full {{ $dots[$status] ?? 'bg-gray-500' }}"></span>{{ $status }}</span>
