<div>
    <button type="button" class="primary-button" wire:click="apply" wire:loading.attr="disabled" wire:target="apply">New ID Application</button>
    @if ($applicationOpen)
        <livewire:registrations.id-application :modal="true" :key="'new-id-application'" />
    @endif
</div>
