<div class="mx-auto max-w-5xl space-y-6">
    @if (!$embedded)<header><a href="{{ route('registrations.index') }}" class="text-button text-sm">Back to registrations</a><h2 class="mt-3 text-3xl font-medium text-osca-primary dark:text-slate-100">Official names and card assets</h2><p class="mt-2 text-slate-500">These settings apply to future ID prints, including reprints. Changes are recorded in the audit trail.</p></header>@else<header><h3 class="text-lg font-semibold">Official card settings</h3><p class="mt-1 text-sm text-slate-500">Changes apply to future prints for all seniors.</p></header>@endif
    @if($notice)<x-feedback>{{ $notice }}</x-feedback>@endif
    <x-benefit-errors />
    <form wire:submit="save" class="space-y-6">
        <x-senior-id.panel title="Authorized signatories">
            <div class="grid gap-8 md:grid-cols-2">
                <div class="space-y-5"><label for="head-name" class="block text-sm">OSCA Head name<input id="head-name" wire:model="oscaHeadName" maxlength="150" required class="mt-2 block w-full"></label><x-senior-id.image-upload property="headSignature" label="OSCA Head signature (optional)" />@if($images['osca_head_signature'])<img src="{{ $images['osca_head_signature'] }}" class="max-h-20 max-w-full bg-white" alt="Current OSCA Head signature"><label class="flex gap-2 text-sm"><input type="checkbox" wire:model="remove" value="osca_head_signature"> Remove current signature</label>@endif</div>
                <div class="space-y-5"><label for="mayor-name" class="block text-sm">Municipal Mayor name<input id="mayor-name" wire:model="mayorName" maxlength="150" required class="mt-2 block w-full"></label><x-senior-id.image-upload property="mayorSignature" label="Municipal Mayor signature (optional)" />@if($images['municipal_mayor_signature'])<img src="{{ $images['municipal_mayor_signature'] }}" class="max-h-20 max-w-full bg-white" alt="Current Mayor signature"><label class="flex gap-2 text-sm"><input type="checkbox" wire:model="remove" value="municipal_mayor_signature"> Remove current signature</label>@endif</div>
            </div>
        </x-senior-id.panel>
        <x-senior-id.panel title="Municipal seal" description="The bundled LGU seal is included by default. Upload an official replacement if needed.">
            <div class="grid gap-8 md:grid-cols-2"><x-senior-id.image-upload property="seal" label="Official municipal seal" /><div><img src="{{ $images['municipal_seal'] }}" class="size-32 object-contain bg-white" alt="Current municipal seal">@if($hasCustomSeal)<label class="mt-4 flex gap-2 text-sm"><input type="checkbox" wire:model="remove" value="municipal_seal"> Use default LGU seal</label>@else<p class="mt-4 text-sm text-slate-500">Using the included LGU seal.</p>@endif</div></div>
        </x-senior-id.panel>
        <footer class="flex justify-end"><button class="primary-button" wire:loading.attr="disabled">Save official settings</button></footer>
    </form>
</div>
