@props(['senior' => null])
@php
    $context = request()->only(['from', 'search', 'search_by', 'stage', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page']);
    $fromRegistrations = ($context['from'] ?? '') === 'registrations';
    $listRoute = $fromRegistrations ? 'registrations.index' : 'senior-citizens.index';
    $listContext = collect($context)->only($fromRegistrations ? ['search', 'search_by', 'stage', 'barangay_id', 'page'] : ['search', 'search_by', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page'])->all();
@endphp
<div class="flex flex-wrap items-center gap-4 text-sm">
    @if ($senior)
        <a href="{{ route('senior-citizens.show', [$senior, ...$context, 'section' => 'id-card']) }}" class="text-button">Back to senior record</a>
    @endif
    <a href="{{ route($listRoute, $listContext) }}" class="text-button">{{ $fromRegistrations ? 'Back to registrations' : 'Back to registry' }}</a>
</div>
