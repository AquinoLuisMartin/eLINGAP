import assert from 'node:assert/strict';
import test from 'node:test';
import initUppercaseSearch, { uppercaseSearch } from '../../resources/js/uppercase-search.js';

function input(value, position = value.length) {
    return {
        value, selectionStart: position, selectionEnd: position, selectionDirection: 'none',
        matches: () => true,
        setSelectionRange(start, end) { this.selectionStart = start; this.selectionEnd = end; },
    };
}

test('typing and pasting converts the actual value to uppercase while preserving the caret', () => {
    const field = input('synthetic ñame 26-10004', 5);
    assert.equal(uppercaseSearch(field), true);
    assert.equal(field.value, 'SYNTHETIC ÑAME 26-10004');
    assert.equal(field.selectionStart, 5);
    assert.equal(uppercaseSearch(field), false);

    const expanded = input('straße', 5);
    uppercaseSearch(expanded);
    assert.equal(expanded.value, 'STRASSE');
    assert.equal(expanded.selectionStart, 6);
});

test('capture handlers normalize before Livewire and wait for composition to finish', () => {
    const listeners = new Map();
    initUppercaseSearch({ addEventListener(name, handler, capture) {
        assert.equal(capture, true);
        listeners.set(name, handler);
    } });
    const field = input('synthetic');
    listeners.get('input')({ target: field, isComposing: true });
    assert.equal(field.value, 'synthetic');
    let updates = 0;
    field.dispatchEvent = event => { assert.equal(event.type, 'input'); updates++; };
    listeners.get('compositionend')({ target: field });
    assert.equal(field.value, 'SYNTHETIC');
    assert.equal(updates, 1);

    const other = input('unchanged');
    other.matches = () => false;
    listeners.get('input')({ target: other });
    assert.equal(other.value, 'unchanged');
});
