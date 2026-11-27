<div>
    <button type="button" class="primary-button gap-2" wire:click="apply" wire:loading.attr="disabled" wire:target="apply">
        <x-admin.icon name="plus" size="16" />
        New ID Application
    </button>
    @if ($applicationOpen)
        <livewire:registrations.id-application :modal="true" :key="'new-id-application-'.$applicationSession" />
    @endif
</div>
