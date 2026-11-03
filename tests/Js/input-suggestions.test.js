import assert from 'node:assert/strict';
import test from 'node:test';
import initInputSuggestions, { disableInputSuggestions } from '../../resources/js/input-suggestions.js';

function control(type = 'text', tagName = 'INPUT') {
    const attributes = new Map();
    return { type, tagName, setAttribute: (name, value) => attributes.set(name, value), getAttribute: name => attributes.get(name) };
}

test('text entry suppresses saved suggestions without changing password and non-text controls', () => {
    for (const type of ['text', 'search', 'email', 'tel', 'url', 'number']) {
        const input = control(type);
        disableInputSuggestions(input);
        assert.equal(input.getAttribute('autocomplete'), 'off');
    }
    const textarea = control('', 'TEXTAREA');
    disableInputSuggestions(textarea);
    assert.equal(textarea.getAttribute('autocomplete'), 'off');

    for (const type of ['password', 'hidden', 'file', 'checkbox', 'radio', 'submit']) {
        const input = control(type);
        input.setAttribute('autocomplete', 'new-password');
        disableInputSuggestions(input);
        assert.equal(input.getAttribute('autocomplete'), 'new-password');
    }
});

test('initial fields, dynamically focused fields and Livewire navigation receive the setting', () => {
    const listeners = new Map();
    const fields = [control('search')];
    initInputSuggestions({
        querySelectorAll: () => fields,
        addEventListener: (name, handler) => listeners.set(name, handler),
    });
    assert.equal(fields[0].getAttribute('autocomplete'), 'off');

    const dynamic = control('email');
    listeners.get('focusin')({ target: dynamic });
    assert.equal(dynamic.getAttribute('autocomplete'), 'off');

    fields.push(control('tel'));
    listeners.get('livewire:navigated')();
    assert.equal(fields[1].getAttribute('autocomplete'), 'off');
});
