@props(['type' => 'success'])
<div {{ $attributes->class(['ui-feedback', 'ui-feedback-error' => $type === 'error', 'ui-feedback-success' => $type !== 'error'])->merge(['role' => $type === 'error' ? 'alert' : 'status']) }}>
    {{ $slot }}
</div>
