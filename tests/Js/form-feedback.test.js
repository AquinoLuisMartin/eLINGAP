import assert from 'node:assert/strict';
import test from 'node:test';
import { beginSubmission } from '../../resources/js/form-feedback.js';

function element() {
    const attributes = new Map();
    return {
        setAttribute: (name, value) => attributes.set(name, value),
        getAttribute: name => attributes.get(name) ?? null,
        removeAttribute: name => attributes.delete(name),
        remove() { this.removed = true; },
    };
}

function submission() {
    const button = { ...element(), name: 'action', value: 'approve', disabled: false };
    const form = {
        ...element(), method: 'post', target: '', id: '', dataset: {}, attributes: [],
        ownerDocument: { createElement: element },
        querySelectorAll: () => [button],
        append(status) { this.status = status; },
    };
    const event = { defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
    return { form, button, event, pending: new Map() };
}

test('ordinary submissions announce progress and block a second submission', () => {
    const { form, button, event, pending } = submission();

    beginSubmission(form, event, pending);
    beginSubmission(form, event, pending);

    assert.equal(event.defaultPrevented, true);
    assert.equal(form.getAttribute('aria-busy'), 'true');
    assert.equal(form.status.textContent, 'Submitting...');
    assert.equal(button.getAttribute('aria-disabled'), 'true');
    assert.equal(button.disabled, false);
    assert.equal(button.name, 'action');
    assert.equal(button.value, 'approve');
});

test('returning to a page restores controls and removes progress feedback', () => {
    const { form, button, event, pending } = submission();
    beginSubmission(form, event, pending);

    pending.get(form)();

    assert.equal(form.getAttribute('aria-busy'), null);
    assert.equal(button.getAttribute('aria-disabled'), null);
    assert.equal(button.getAttribute('data-ui-busy'), null);
    assert.equal(form.status.removed, true);
});

test('cancelled confirmations, GET filters, print windows and Livewire keep their submission behavior', () => {
    for (const change of [
        ({ event }) => { event.defaultPrevented = true; },
        ({ form }) => { form.method = 'get'; },
        ({ form }) => { form.target = '_blank'; },
        ({ form }) => { form.attributes = [{ name: 'wire:submit' }]; },
        ({ form }) => { form.id = 'login-form'; },
    ]) {
        const page = submission();
        change(page);

        beginSubmission(page.form, page.event, page.pending);

        assert.equal(page.pending.size, 0);
        assert.equal(page.form.getAttribute('aria-busy'), null);
    }
});
