<div class="relative" x-data="{
    enhanced: false,
    open: false,
    selected: {{ \Illuminate\Support\Js::from((string) request('barangay_id', '')) }},
    label: {{ \Illuminate\Support\Js::from($barangays->firstWhere('id', request('barangay_id'))?->name ?? 'All barangays') }},
    show() {
        this.open = true;
        this.$nextTick(() => this.$refs.options.querySelector('[aria-selected=true]').focus());
    },
    choose(value, label) {
        this.selected = value;
        this.label = label;
        this.open = false;
        this.$refs.trigger.focus();
    },
    move(delta) {
        const options = Array.from(this.$refs.options.querySelectorAll('[role=option]'));
        const index = options.indexOf(document.activeElement);
        options[(index + delta + options.length) % options.length].focus();
    }
}" x-init="enhanced = true" @click.outside="open = false"
    @focusout="if (!$el.contains($event.relatedTarget)) open = false"
    @keydown.escape.stop.prevent="open = false; $refs.trigger.focus()">
    <label id="registry-barangay-label" for="registry-barangay-native" :for="enhanced ? 'registry-barangay-trigger' : 'registry-barangay-native'" class="field-label">Barangay</label>
    <select id="registry-barangay-native" name="barangay_id" x-show="!enhanced" :disabled="enhanced">
        <option value="">All barangays</option>
        @foreach ($barangays as $barangay)
            <option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>
        @endforeach
    </select>
    <input type="hidden" name="barangay_id" disabled :disabled="!enhanced" :value="selected">
    <button id="registry-barangay-trigger" type="button" x-cloak x-show="enhanced" x-ref="trigger" class="barangay-filter-trigger"
        aria-labelledby="registry-barangay-label registry-barangay-value" aria-haspopup="listbox"
        aria-controls="registry-barangay-options" :aria-expanded="open"
        @click="open ? open = false : show()" @keydown.arrow-down.prevent="show()" @keydown.arrow-up.prevent="show()">
        <span id="registry-barangay-value" class="truncate" x-text="label"></span>
        <x-admin.icon name="chevron-down" size="16" />
    </button>
    <div id="registry-barangay-options" role="listbox" aria-labelledby="registry-barangay-label"
        class="barangay-filter-options" x-cloak x-show="open" x-ref="options"
        @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
        @keydown.home.prevent="$el.firstElementChild.focus()" @keydown.end.prevent="$el.lastElementChild.focus()">
        <button type="button" role="option" tabindex="-1" :aria-selected="selected === ''"
            @click="choose('', 'All barangays')">All barangays</button>
        @foreach ($barangays as $barangay)
            <button type="button" role="option" tabindex="-1" :aria-selected="selected === '{{ $barangay->id }}'"
                @click="choose({{ \Illuminate\Support\Js::from((string) $barangay->id) }}, {{ \Illuminate\Support\Js::from($barangay->name) }})">{{ $barangay->name }}</button>
        @endforeach
    </div>
</div>
