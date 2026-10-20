@php($filterId = $filterId ?? 'registry-barangay')
<div class="relative flex min-w-0 flex-col gap-[7px]" x-data="{
    enhanced: false,
    open: false,
    query: '',
    above: false,
    menuHeight: 256,
    selected: {{ \Illuminate\Support\Js::from((string) request('barangay_id', '')) }},
    label: {{ \Illuminate\Support\Js::from($barangays->firstWhere('id', request('barangay_id'))?->name ?? 'All barangays') }},
    show() {
        this.query = '';
        const rect = this.$refs.trigger.getBoundingClientRect();
        const below = window.innerHeight - rect.bottom - 14;
        const above = rect.top - 14;
        this.above = below < 256 && above > below;
        this.menuHeight = Math.min(304, this.above ? above : below);
        this.open = true;
        this.$nextTick(() => {
            const option = this.$refs.options.querySelector('[aria-selected=true]') ?? this.visibleOptions()[0];
            option.focus({ preventScroll: true });
            this.$refs.options.scrollTop = option.offsetTop - this.$refs.options.offsetTop;
        });
    },
    choose(value, label) {
        this.selected = value;
        this.label = label;
        this.open = false;
        this.$refs.trigger.focus();
    },
    move(delta) {
        const options = this.visibleOptions();
        if (!options.length) return;
        const index = options.indexOf(document.activeElement);
        options[(index + delta + options.length) % options.length].focus();
    },
    visibleOptions() {
        return Array.from(this.$refs.options.querySelectorAll('[role=option]')).filter(option => option.getClientRects().length);
    },
    matches(label) {
        return label.toLocaleLowerCase().includes(this.query.trim().toLocaleLowerCase());
    }
}" x-init="enhanced = true" @click.outside="open = false"
    @resize.window="open = false"
    @focusout="if (!$el.contains($event.relatedTarget)) open = false"
    @keydown.escape.stop.prevent="open = false; $refs.trigger.focus()">
    <label id="{{ $filterId }}-label" for="{{ $filterId }}-native" :for="enhanced ? '{{ $filterId }}-trigger' : '{{ $filterId }}-native'" class="field-label">Barangay</label>
    <select id="{{ $filterId }}-native" name="barangay_id" x-show="!enhanced" :disabled="enhanced">
        <option value="">All barangays</option>
        @foreach ($barangays as $barangay)
            <option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>
        @endforeach
    </select>
    <input type="hidden" name="barangay_id" disabled :disabled="!enhanced" :value="selected">
    <button id="{{ $filterId }}-trigger" type="button" x-cloak x-show="enhanced" x-ref="trigger" class="barangay-filter-trigger"
        aria-labelledby="{{ $filterId }}-label {{ $filterId }}-value" aria-haspopup="listbox"
        aria-controls="{{ $filterId }}-options" :aria-expanded="open"
        @click="open ? open = false : show()" @keydown.arrow-down.prevent="show()" @keydown.arrow-up.prevent="show()">
        <span id="{{ $filterId }}-value" class="min-w-0 truncate text-left" x-text="label"></span>
        <x-admin.icon name="chevron-down" size="16" />
    </button>
    <div class="barangay-filter-options" x-cloak x-show="open"
        :style="{ top: above ? 'auto' : 'calc(100% + 6px)', bottom: above ? 'calc(100% + 6px)' : 'auto', maxHeight: menuHeight + 'px' }">
        <div class="shrink-0 border-b border-[var(--line)] p-2">
            <input type="search" x-model="query" aria-label="Search barangays" aria-controls="{{ $filterId }}-options"
                placeholder="Search barangays" class="w-full" @keydown.enter.prevent @keydown.arrow-down.prevent="visibleOptions()[0]?.focus()">
        </div>
        <div id="{{ $filterId }}-options" role="listbox" aria-labelledby="{{ $filterId }}-label" x-ref="options" class="min-h-0 overflow-y-auto overscroll-contain p-1"
        @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
        @keydown.home.prevent="visibleOptions()[0]?.focus()" @keydown.end.prevent="visibleOptions().at(-1)?.focus()">
        <button type="button" role="option" tabindex="-1" :aria-selected="selected === ''" x-show="matches('All barangays')"
            @click="choose('', 'All barangays')">All barangays</button>
        @foreach ($barangays as $barangay)
            <button type="button" role="option" tabindex="-1" :aria-selected="selected === '{{ $barangay->id }}'" x-show="matches({{ \Illuminate\Support\Js::from($barangay->name) }})"
                @click="choose({{ \Illuminate\Support\Js::from((string) $barangay->id) }}, {{ \Illuminate\Support\Js::from($barangay->name) }})">{{ $barangay->name }}</button>
        @endforeach
        </div>
        <p x-show="!Array.from($refs.options.querySelectorAll('[role=option]')).some(option => matches(option.textContent))" role="status" class="p-3 text-sm text-[var(--muted)]">No barangays found.</p>
    </div>
</div>
