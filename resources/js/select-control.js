export function readOptions(select) {
    return Array.from(select.options, (option, index) => ({
        index,
        label: option.label,
        value: option.value,
        hidden: option.hidden,
        disabled: option.disabled || (option.parentElement?.tagName === 'OPTGROUP' && option.parentElement.disabled),
    }));
}

export function filterOptions(options, query) {
    const term = query.trim().toLocaleLowerCase();
    return options.filter(option => !option.hidden && option.label.toLocaleLowerCase().includes(term));
}

export function menuPosition(rect, width, height) {
    const gap = 6;
    const margin = 8;
    const below = height - rect.bottom - gap - margin;
    const above = rect.top - gap - margin;
    const upwards = below < 240 && above > below;
    const menuHeight = Math.max(0, Math.min(304, upwards ? above : below));
    const menuWidth = Math.max(0, Math.min(rect.width, width - margin * 2));
    return {
        left: `${Math.max(margin, Math.min(rect.left, width - menuWidth - margin))}px`,
        top: upwards ? 'auto' : `${rect.bottom + gap}px`,
        bottom: upwards ? `${height - rect.top + gap}px` : 'auto',
        width: `${menuWidth}px`,
        maxHeight: `${menuHeight}px`,
    };
}

export default function uiSelect({ searchable = null } = {}) {
    let observer;
    let reset;
    let originalTabindex;
    let labels = [];
    const focusLabel = event => {
        event.preventDefault();
        event.currentTarget.parentElement.querySelector('.ui-select-trigger')?.focus();
    };

    return {
        enhanced: false,
        open: false,
        query: '',
        id: '',
        label: '',
        description: null,
        required: false,
        disabled: false,
        invalidState: false,
        message: '',
        options: [],
        selectedIndex: -1,
        position: {},

        get selectedLabel() {
            return this.options.find(option => option.index === this.selectedIndex)?.label ?? 'Select an option';
        },
        get searchEnabled() {
            return searchable ?? (/barangay|program/i.test(`${this.label} ${this.$refs.native.name}`)
                || this.options.filter(option => option.value !== '').length > 7);
        },
        get filteredOptions() {
            return filterOptions(this.options, this.query);
        },

        init() {
            const native = this.$refs.native;
            this.id = native.id || this.$id('select');
            native.id = this.id;
            if (native.multiple || native.size > 1 || window.matchMedia('(forced-colors: active)').matches) {
                this.sync();
                return;
            }
            originalTabindex = native.getAttribute('tabindex');
            native.tabIndex = -1;
            this.sync();
            this.enhanced = true;
            labels = Array.from(native.labels ?? []);
            const visibleLabel = native.parentElement?.parentElement.querySelector('[data-select-label]');
            if (visibleLabel && !labels.includes(visibleLabel)) labels.push(visibleLabel);
            labels.forEach(label => label.addEventListener('click', focusLabel));
            observer = new MutationObserver(() => this.sync());
            observer.observe(native, { attributes: true, childList: true, subtree: true, characterData: true });
            for (const fieldset of native.closest('form')?.querySelectorAll('fieldset') ?? []) {
                if (fieldset.contains(native)) observer.observe(fieldset, { attributes: true, attributeFilter: ['disabled'] });
            }
            reset = () => setTimeout(() => { this.message = ''; this.close(); this.sync(); }, 0);
            native.form?.addEventListener('reset', reset);
        },

        destroy() {
            observer?.disconnect();
            labels.forEach(label => label.removeEventListener('click', focusLabel));
            this.$refs.native.form?.removeEventListener('reset', reset);
            if (originalTabindex === null) this.$refs.native.removeAttribute('tabindex');
            else if (originalTabindex !== undefined) this.$refs.native.setAttribute('tabindex', originalTabindex);
        },

        sync() {
            const native = this.$refs.native;
            if (this.enhanced && native.getAttribute('tabindex') !== '-1') native.tabIndex = -1;
            if (this.id && native.id !== this.id) native.id = this.id;
            this.options = readOptions(native);
            this.selectedIndex = native.selectedIndex;
            this.disabled = native.matches(':disabled');
            this.required = native.required;
            this.label = native.getAttribute('aria-label') || Array.from(native.labels ?? [])
                .map(label => Array.from(label.childNodes)
                    .filter(node => node.nodeType === 3 || (node.nodeType === 1 && node.tagName === 'SPAN'
                        && node.getAttribute('aria-hidden') !== 'true' && !node.querySelector('input, select, button')))
                    .map(node => node.textContent).join(' ')).join(' ').replace(/\s+/g, ' ').trim() || 'Options';
            const labelledBy = native.getAttribute('aria-labelledby');
            if (labelledBy) this.label = labelledBy.split(/\s+/).map(id => document.getElementById(id)?.textContent ?? '').join(' ').trim() || this.label;
            if (native.validity.valid) this.message = '';
            this.invalidState = native.getAttribute('aria-invalid') === 'true' || Boolean(this.message);
            this.description = [native.getAttribute('aria-describedby'), this.message ? this.id + '-error' : ''].filter(Boolean).join(' ') || null;
            if (this.disabled) this.close();
        },

        show(focusLast = false) {
            this.sync();
            if (this.disabled) return;
            this.query = '';
            this.reposition();
            this.$dispatch('ui-select-open', this.$el);
            this.open = true;
            this.$nextTick(() => {
                const buttons = this.optionButtons();
                const selected = buttons.find(button => Number(button.dataset.index) === this.selectedIndex);
                const target = selected || (focusLast ? buttons.at(-1) : buttons[0]);
                if (this.searchEnabled && !focusLast) this.$refs.search.focus({ preventScroll: true });
                else target?.focus({ preventScroll: true });
                selected?.scrollIntoView({ block: 'nearest' });
            });
        },

        reposition() {
            this.position = menuPosition(this.$refs.trigger.getBoundingClientRect(), window.innerWidth, window.innerHeight);
        },

        close(restoreFocus = false) {
            const wasOpen = this.open;
            this.open = false;
            if (restoreFocus && wasOpen) this.$refs.trigger.focus({ preventScroll: true });
        },

        tab(event) {
            if (!this.open) return;
            const scope = this.$el.closest('[role="dialog"]') ?? document;
            const controls = Array.from(scope.querySelectorAll('a[href], button, input, select, textarea, [tabindex]'))
                .filter(control => !control.disabled && control.tabIndex >= 0 && control.getClientRects().length
                    && !this.$refs.menu.contains(control));
            const index = controls.indexOf(this.$refs.trigger);
            const next = controls[index + (event.shiftKey ? -1 : 1)];
            this.close();
            if (next) {
                event.preventDefault();
                next.focus();
            } else {
                this.$refs.trigger.focus();
            }
        },

        choose(index) {
            const native = this.$refs.native;
            const option = this.options.find(option => option.index === index);
            if (native.matches(':disabled') || !option || option.disabled || option.hidden) return;
            native.selectedIndex = index;
            native.dispatchEvent(new Event('input', { bubbles: true }));
            native.dispatchEvent(new Event('change', { bubbles: true }));
            this.sync();
            this.close(true);
        },

        invalid() {
            if (!this.enhanced) return;
            const native = this.$refs.native;
            this.message = native.validationMessage;
            this.sync();
            const first = native.form?.querySelector?.('input:invalid, select:invalid, textarea:invalid') ?? native;
            if (first === native) this.$nextTick(() => this.$refs.trigger.focus());
        },

        optionButtons() {
            return Array.from(this.$refs.options.querySelectorAll('[role="option"]:not(:disabled)'));
        },

        triggerKey(event) {
            if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
                event.preventDefault();
                this.open ? this.close(true) : this.show(event.key === 'ArrowUp');
            } else if (['Home', 'End'].includes(event.key)) {
                event.preventDefault();
                this.show(event.key === 'End');
                this.$nextTick(() => (event.key === 'End' ? this.optionButtons().at(-1) : this.optionButtons()[0])?.focus());
            }
        },

        menuKey(event) {
            const buttons = this.optionButtons();
            const index = buttons.indexOf(document.activeElement);
            let target;
            if (event.key === 'ArrowDown') target = buttons[(index + 1) % buttons.length];
            else if (event.key === 'ArrowUp') target = buttons[(index < 0 ? buttons.length : index) - 1] ?? buttons.at(-1);
            else if (event.key === 'Home' && event.target !== this.$refs.search) target = buttons[0];
            else if (event.key === 'End' && event.target !== this.$refs.search) target = buttons.at(-1);
            else return;
            event.preventDefault();
            target?.focus();
        },
    };
}
