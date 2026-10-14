<section aria-label="Registration requirements" class="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
    @if ($senior->status->value === 'PENDING')
        <h3 class="text-base font-semibold">Review requirements</h3>
        <p class="mt-2 max-w-2xl text-sm text-slate-500">{{ $senior->review_reason ?: 'This existing registration needs its requirements checked before verification.' }}</p>
        @can('verify', $senior)
            <form wire:submit="verify" class="mt-4 space-y-3">
                <label class="flex max-w-2xl items-start gap-2 text-sm"><input type="checkbox" wire:model="documentsChecked" class="mt-1"><span>I checked identity and age, residency, and representative authorization when applicable, and resolved the document concern above.</span></label>
                @error('documentsChecked')<p role="alert" class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                <button type="submit" class="primary-button" wire:loading.attr="disabled">Verify requirements</button>
                <span wire:loading wire:target="verify" role="status" class="text-sm text-slate-500">Verifying registration...</span>
            </form>
        @endcan
    @elseif ($senior->status->value === 'VERIFIED')
        <p role="status" class="text-sm text-emerald-700 dark:text-emerald-300">Requirements verified {{ $senior->verified_at?->format('M j, Y') }}. This registration is ready for ID preparation.</p>
    @else
        <p class="text-sm text-slate-500">{{ ucfirst(strtolower($senior->status->value)) }} registration. Requirements cannot be verified in this state.</p>
    @endif
</section>
