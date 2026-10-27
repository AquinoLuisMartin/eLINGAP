import assert from 'node:assert/strict';
import test from 'node:test';
import uiSelect, { filterOptions, menuPosition, readOptions } from '../../resources/js/select-control.js';

function control() {
    const attributes = new Map();
    const native = new EventTarget();
    Object.assign(native, {
        options: [
            { label: 'Select program', value: '', disabled: false },
            { label: 'Synthetic assistance', value: '42', disabled: false },
            { label: 'Other - enter a new program', value: 'other', disabled: false },
        ],
        selectedIndex: 0,
        name: 'program_id',
        id: 'program',
        labels: [],
        validity: { valid: true },
        required: true,
        disabled: false,
        validationMessage: '',
        getAttribute: name => attributes.get(name) ?? null,
        setAttribute: (name, value) => attributes.set(name, value),
        removeAttribute: name => attributes.delete(name),
        matches: () => native.disabled,
        closest: () => null,
        form: new EventTarget(),
    });
    Object.defineProperty(native, 'value', { get: () => native.options[native.selectedIndex]?.value });
    let focused = null;
    const buttons = native.options.map((option, index) => ({
        dataset: { index: String(index) },
        focus() { focused = index; globalThis.document.activeElement = this; },
        scrollIntoView() {},
    }));
    const state = uiSelect();
    state.$refs = {
        native,
        trigger: { focus() { focused = 'trigger'; }, getBoundingClientRect: () => ({ top: 40, bottom: 84, left: 10, width: 240 }) },
        options: { querySelectorAll: () => buttons.filter(button => !native.options[Number(button.dataset.index)]?.disabled) },
        search: { focus() { focused = 'search'; } },
    };
    state.$nextTick = callback => callback();
    state.$dispatch = () => {};
    state.$id = () => 'generated-select';
    globalThis.document = { activeElement: null, getElementById: () => null };
    globalThis.window = { innerWidth: 360, innerHeight: 768, matchMedia: () => ({ matches: false }) };
    globalThis.MutationObserver = class { observe() {} disconnect() {} };
    state.sync();
    return { state, native, buttons, focused: () => focused };
}

test('search trims whitespace, ignores case and excludes hidden options', () => {
    const options = [
        { label: 'Synthetic assistance' },
        { label: 'Other - enter a new program' },
        { label: 'Synthetic hidden', hidden: true },
    ];

    assert.deepEqual(filterOptions(options, '  SYNTHETIC  '), [options[0]]);
    assert.deepEqual(filterOptions(options, 'missing'), []);
});

test('disabled optgroups keep their options unavailable', () => {
    const options = readOptions({ options: [{ label: 'Unavailable', value: '1', parentElement: { tagName: 'OPTGROUP', disabled: true } }] });

    assert.equal(options[0].disabled, true);
});

test('selection updates the native form value before input and change handlers run', () => {
    const { state, native, focused } = control();
    const received = [];
    native.addEventListener('input', event => received.push([event.type, native.value, event.bubbles]));
    native.addEventListener('change', event => received.push([event.type, native.value, event.bubbles]));
    state.open = true;

    state.choose(2);

    assert.deepEqual(received, [['input', 'other', true], ['change', 'other', true]]);
    assert.equal(state.selectedLabel, 'Other - enter a new program');
    assert.equal(state.open, false);
    assert.equal(focused(), 'trigger');
});

test('disabled fields and disabled or hidden options cannot be selected', () => {
    const { state, native } = control();
    native.disabled = true;
    state.choose(1);
    assert.equal(native.value, '');
    native.disabled = false;
    native.options[1].disabled = true;
    native.options[2].hidden = true;
    state.sync();

    state.choose(1);
    state.choose(2);

    assert.equal(native.value, '');
});

test('server value and option changes update the label without dispatching a user change', () => {
    const { state, native } = control();
    let changes = 0;
    native.addEventListener('change', () => changes++);
    native.options[1].label = 'Updated assistance';
    native.selectedIndex = 1;

    state.sync();

    assert.equal(state.selectedLabel, 'Updated assistance');
    assert.equal(changes, 0);
});

test('search is available for short program lists and lists with more than seven values', () => {
    const { state, native } = control();
    assert.equal(state.searchEnabled, true);
    native.name = 'status';
    assert.equal(state.searchEnabled, false);
    native.options = Array.from({ length: 8 }, (_, index) => ({ label: String(index), value: String(index) }));
    state.sync();
    assert.equal(state.searchEnabled, true);
});

test('opening a searchable field focuses search and resets the previous query', () => {
    const { state, focused } = control();
    state.query = 'previous search';

    state.show();

    assert.equal(state.open, true);
    assert.equal(state.query, '');
    assert.equal(focused(), 'search');
});

test('arrow keys skip disabled options and wrap without selecting or submitting', () => {
    const { state, native, buttons, focused } = control();
    native.options[1].disabled = true;
    globalThis.document.activeElement = buttons[0];
    let prevented = false;

    state.menuKey({ key: 'ArrowDown', preventDefault() { prevented = true; } });

    assert.equal(focused(), 2);
    assert.equal(native.value, '');
    assert.equal(prevented, true);
});

test('Home and End in search retain text editing behavior', () => {
    const { state, focused } = control();
    for (const key of ['Home', 'End']) {
        state.menuKey({ key, target: state.$refs.search, preventDefault() { assert.fail('Search editing must not be intercepted'); } });
    }
    assert.equal(focused(), null);
});

test('Tab leaves the popup for the next control and Shift Tab returns to the previous control', () => {
    const { state, focused } = control();
    let destination;
    const previous = { disabled: false, tabIndex: 0, getClientRects: () => [1], focus() { destination = 'previous'; } };
    const next = { disabled: false, tabIndex: 0, getClientRects: () => [1], focus() { destination = 'next'; } };
    Object.assign(state.$refs.trigger, { tabIndex: 0, getClientRects: () => [1] });
    state.$el = { closest: () => null };
    state.$refs.menu = { contains: () => false };
    globalThis.document.querySelectorAll = () => [previous, state.$refs.trigger, next];

    for (const shiftKey of [false, true]) {
        state.open = true;
        let prevented = false;
        state.tab({ shiftKey, preventDefault() { prevented = true; } });

        assert.equal(destination, shiftKey ? 'previous' : 'next');
        assert.equal(state.open, false);
        assert.equal(prevented, true);
    }
    assert.equal(focused(), null);
});

test('an invalid required field exposes its message and focuses the visible control', () => {
    const { state, native, focused } = control();
    state.enhanced = true;
    state.id = native.id;
    native.validity.valid = false;
    native.validationMessage = 'Please select an item in the list.';

    state.invalid();

    assert.equal(state.invalidState, true);
    assert.equal(state.message, 'Please select an item in the list.');
    assert.equal(state.description, 'program-error');
    assert.equal(focused(), 'trigger');
    native.validity.valid = true;
    state.sync();
    assert.equal(state.message, '');
});

test('reset restores the displayed native value and clears local validation', async () => {
    const { state, native } = control();
    state.init();
    native.selectedIndex = 2;
    state.sync();
    state.open = true;
    state.message = 'Previous validation';

    native.form.dispatchEvent(new Event('reset'));
    native.selectedIndex = 0;
    await new Promise(resolve => setTimeout(resolve, 5));

    assert.equal(state.selectedLabel, 'Select program');
    assert.equal(state.open, false);
    assert.equal(state.message, '');
    state.destroy();
});

test('later invalid dropdowns do not steal focus from the first invalid form field', () => {
    const { state, native, focused } = control();
    state.enhanced = true;
    native.validity.valid = false;
    native.validationMessage = 'Select a program.';
    native.form.querySelector = () => ({ id: 'earlier-invalid-field' });

    state.invalid();

    assert.equal(state.message, 'Select a program.');
    assert.equal(focused(), null);
});

test('multiple selects and forced colors retain the native control', () => {
    const { state, native } = control();
    native.multiple = true;
    state.init();
    assert.equal(state.enhanced, false);
    native.multiple = false;
    globalThis.window.matchMedia = () => ({ matches: true });
    state.init();
    assert.equal(state.enhanced, false);
});

test('menus open above near the viewport bottom and fit narrow screens', () => {
    const position = menuPosition({ top: 650, bottom: 694, left: 300, width: 400 }, 360, 720);

    assert.deepEqual(position, { top: 'auto', bottom: '76px', left: '8px', width: '344px', maxHeight: '304px' });
});
