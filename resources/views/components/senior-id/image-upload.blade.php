@props(['property', 'label', 'square' => false, 'saveAction' => null, 'initialImage' => null])
<div x-data="idImageEditor($wire, @js($property), @js($square), @js($saveAction))" class="space-y-4">
    @if($initialImage)<button type="button" class="secondary-button" @click="loadSaved(@js($initialImage))" :disabled="busy">Adjust saved image</button>@endif
    <label class="block text-sm font-normal">
        {{ $label }}
        <input type="file" accept="image/jpeg,image/png" @change="load($event)" :disabled="busy" class="mt-2 block w-full" aria-describedby="{{ $property }}-help">
    </label>
    <p id="{{ $property }}-help" class="text-sm text-slate-500">JPEG or PNG, up to 2MB.@if ($square) Adjust the square crop before saving.@endif</p>
    <p x-show="error" x-text="error" role="alert" class="text-sm text-red-700 dark:text-red-300" x-cloak></p>
    <div x-show="ready" x-cloak class="space-y-4" wire:ignore>
        <div class="flex min-h-52 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 p-4">
            <canvas x-ref="canvas" :class="square ? 'aspect-square w-full max-w-72' : 'max-h-48 max-w-full'" aria-label="Image preview"></canvas>
        </div>
        @if ($square)
            <p class="text-sm" x-text="needsCrop ? 'This photo needs adjustment to fit the 1x1 area.' : 'The photo fits a square. You can adjust the framing.'"></p>
            <div class="grid gap-4 sm:grid-cols-3">
                <label class="block text-sm">Zoom<input type="range" min="1" max="3" step="0.01" x-model.number="zoom" @input="draw()" class="mt-2 w-full"></label>
                <label class="block text-sm">Horizontal position<input type="range" min="0" max="1" step="0.01" x-model.number="positionX" @input="draw()" class="mt-2 w-full"></label>
                <label class="block text-sm">Vertical position<input type="range" min="0" max="1" step="0.01" x-model.number="positionY" @input="draw()" class="mt-2 w-full"></label>
            </div>
        @endif
        <button type="button" class="secondary-button" @click="upload()" :disabled="busy"><span x-text="busy ? 'Saving image...' : 'Use this image'"></span></button>
    </div>
    <p x-show="uploaded" role="status" class="text-sm text-emerald-700 dark:text-emerald-300" x-cloak>Image prepared.@if (!$saveAction) Save the settings below to apply it.@endif</p>
    @error($property)<p class="text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
</div>
