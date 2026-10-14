<div x-data="signaturePad($wire)" wire:ignore class="space-y-3">
    <p id="signature-pad-help" class="text-sm text-slate-500">Draw with a mouse, finger, or stylus, then save the signature.</p>
    <canvas x-ref="canvas" width="900" height="300" aria-label="Draw signature" aria-describedby="signature-pad-help"
        class="aspect-[3/1] w-full touch-none rounded-lg border border-slate-300 bg-white dark:border-slate-600"
        @pointerdown.prevent="start($event)" @pointermove.prevent="move($event)" @pointerup="stop($event)" @pointercancel="stop($event)" @lostpointercapture="stop($event)"></canvas>
    <div class="flex flex-wrap gap-2">
        <button type="button" class="secondary-button" @click="undo()" :disabled="busy || !strokes.length || pointerId !== null">Undo</button>
        <button type="button" class="secondary-button" @click="clear()" :disabled="busy || !strokes.length || pointerId !== null">Clear</button>
        <button type="button" class="primary-button" @click="save()" :disabled="busy || !strokes.length || pointerId !== null" x-text="busy ? 'Saving signature...' : 'Save signature'"></button>
    </div>
    <p x-show="error" x-text="error" role="alert" class="text-sm text-red-700 dark:text-red-300" x-cloak></p>
    <p x-show="saved" role="status" class="text-sm text-emerald-700 dark:text-emerald-300" x-cloak>Signature saved.</p>
</div>
