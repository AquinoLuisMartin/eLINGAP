@props(['searchable' => null, 'label' => null])
@php
    $model = $attributes->wire('model')->value();
    $alpineModel = $attributes->get('x-model');
    $field = $model ?: $attributes->get('name');
    $defaults = ['aria-invalid' => $field && isset($errors) && $errors->has($field) ? 'true' : 'false'];
    if ($label) {
        $defaults['aria-label'] = $label;
    }
@endphp
<span {{ $attributes->only('class')->class(['ui-select']) }} x-data="uiSelect({ searchable: @js($searchable) })"
    @if ($model) x-effect="$wire.$get(@js($model)); $nextTick(() => sync())"
    @elseif ($alpineModel) x-effect="{{ $alpineModel }}; $nextTick(() => sync())" @endif
    @click.outside="close()" @focusout="$nextTick(() => { if (!$el.contains(document.activeElement)) close() })"
    @keydown.escape="if (open) { $event.stopPropagation(); $event.preventDefault(); close(true) }"
    @keydown.tab="tab($event)"
    @resize.window="if (open) reposition()" @scroll.window.capture="if (!$refs.menu.contains($event.target)) close()"
    @ui-select-open.window="if ($event.detail !== $el) close()">
    <select {{ $attributes->except('class')->class(['w-full'])->merge($defaults) }} x-ref="native" :class="{ 'ui-select-native': enhanced }"
        :aria-hidden="enhanced ? 'true' : null" @focus="if (enhanced) $refs.trigger.focus()"
        @input="sync()" @change="sync()" @invalid="if (enhanced) { $event.preventDefault(); invalid() }">
        {{ $slot }}
    </select>
    <span wire:ignore x-cloak x-show="enhanced" class="ui-select-custom">
        <button type="button" x-ref="trigger" class="ui-select-trigger" :id="id + '-trigger'" role="combobox"
            aria-haspopup="listbox" :aria-expanded="open" :aria-controls="id + '-options'" :aria-label="label"
            :aria-describedby="description" :aria-required="required" :aria-invalid="invalidState" :disabled="disabled"
            @click.prevent="open ? close() : show()" @keydown="triggerKey($event)">
            <span class="min-w-0 truncate text-left" x-text="selectedLabel"></span>
            <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6" /></svg>
        </button>
        <span x-ref="menu" x-show="open" class="ui-select-menu" :style="position" @keydown="menuKey($event)">
            <span x-show="searchEnabled" class="ui-select-search">
                <input x-ref="search" type="search" x-model="query" :aria-label="'Search ' + label.toLocaleLowerCase()"
                    :placeholder="'Search ' + label.toLocaleLowerCase()" :aria-controls="id + '-options'" autocomplete="off"
                    @keydown.enter.prevent @keydown.space.stop @keydown.home.stop @keydown.end.stop>
            </span>
            <span x-ref="options" :id="id + '-options'" role="listbox" :aria-label="label" class="ui-select-options">
                <template x-for="option in filteredOptions" :key="option.index">
                    <button type="button" role="option" tabindex="-1" :data-index="option.index"
                        :aria-selected="selectedIndex === option.index" :aria-disabled="option.disabled" :disabled="option.disabled"
                        @click.prevent.stop="choose(option.index)" @keydown.enter.prevent.stop="choose(option.index)"
                        @keydown.space.prevent.stop="choose(option.index)" x-text="option.label"></button>
                </template>
            </span>
            <span x-show="!filteredOptions.length" role="status" class="ui-select-empty">No options found.</span>
        </span>
        <span x-show="message" :id="id + '-error'" role="alert" class="field-error" x-text="message"></span>
    </span>
</span>
