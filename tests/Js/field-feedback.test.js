import assert from 'node:assert/strict';
import test from 'node:test';
import { fieldName, fieldMessage, syncFields } from '../../resources/js/field-feedback.js';

test('validation names match Livewire bindings, file editors and nested HTML fields', () => {
    assert.equal(fieldName({ attributes: [{ name: 'wire:model.live.debounce.300ms', value: 'form.name' }], dataset: {}, name: '' }), 'form.name');
    assert.equal(fieldName({ attributes: [], dataset: { validationField: 'seal' }, name: '' }), 'seal');
    assert.equal(fieldName({ attributes: [], dataset: {}, name: 'recipients[2][name]' }), 'recipients.2.name');
    assert.equal(fieldName({ attributes: [], dataset: {}, name: 'senior_ids[]' }), 'senior_ids.*');
});

test('field errors handle arrays, scalar messages, array entries and unrelated errors', () => {
    const errors = { 'form.name': ['Enter a name.'], 'senior_ids.2': ['Select an issued ID.'], process: 'Funds are insufficient.' };
    assert.equal(fieldMessage(errors, 'form.name'), 'Enter a name.');
    assert.equal(fieldMessage(errors, 'senior_ids.*'), 'Select an issued ID.');
    assert.equal(fieldMessage({ senior_ids: ['Select at least one ID.'] }, 'senior_ids.*'), 'Select at least one ID.');
    assert.equal(fieldMessage(errors, 'process'), 'Funds are insufficient.');
    assert.equal(fieldMessage(errors, 'form.email'), '');
});

test('required markers are added once and removed when a conditional field is disabled', () => {
    const originalDocument = globalThis.document;
    const label = {
        marker: null,
        querySelector(selector) { return selector.includes('required') ? this.marker : null; },
        append(marker) { this.marker = marker; },
    };
    globalThis.document = {
        getElementById: () => null,
        createElement: () => ({ dataset: {}, setAttribute() {}, remove() { label.marker = null; } }),
    };
    const control = {
        attributes: [], dataset: {}, name: 'document', required: true, disabled: false, labels: [label],
        getAttribute: () => null, hasAttribute: () => false, closest: () => null,
    };
    const scope = { children: [], querySelectorAll: () => [control], hasAttribute: () => false };
    try {
        syncFields(scope);
        const marker = label.marker;
        assert.equal(marker.textContent, '*');
        syncFields(scope);
        assert.equal(label.marker, marker);
        control.disabled = true;
        syncFields(scope);
        assert.equal(label.marker, null);
    } finally {
        globalThis.document = originalDocument;
    }
});

test('server validation adds accessible field feedback and process alerts and clears resolved errors', () => {
    const nodes = new Map();
    function element() {
        const attributes = new Map();
        return {
            dataset: {}, children: [], textContent: '',
            getAttribute: key => attributes.get(key) ?? null,
            setAttribute(key, value) { attributes.set(key, value); },
            removeAttribute: key => attributes.delete(key),
            hasAttribute(key) { return attributes.has(key) || (key === 'data-feedback-invalid' && 'feedbackInvalid' in this.dataset) || (key === 'data-validation-summary' && 'validationSummary' in this.dataset); },
            querySelector: () => null,
            closest: () => null,
            remove() { this.removed = true; },
        };
    }
    const originalDocument = globalThis.document;
    globalThis.document = { getElementById: id => nodes.get(id), createElement: element };
    const input = Object.assign(element(), { attributes: [], name: 'amount', labels: [], required: false });
    input.after = error => { nodes.set(error.id, error); };
    const scope = element();
    scope.querySelectorAll = () => [input];
    scope.prepend = alert => scope.children.unshift(alert);
    try {
        syncFields(scope, { amount: ['Enter an amount.'], process: ['Funding is unavailable.'] });

        assert.equal(input.getAttribute('aria-invalid'), 'true');
        const error = nodes.get(input.getAttribute('aria-describedby'));
        assert.equal(error.textContent, 'Enter an amount.');
        assert.equal(error.getAttribute('role'), 'alert');
        assert.match(scope.children[0].textContent, /Funding is unavailable/);

        syncFields(scope, {});

        assert.equal(input.getAttribute('aria-invalid'), null);
        assert.equal(input.getAttribute('aria-describedby'), null);
        assert.equal(error.removed, true);
        assert.equal(scope.children[0].removed, true);

        input.dataset.errorDisplay = 'outline';
        syncFields(scope, { amount: ['Please check all required document requirements before continuing.'] });

        assert.equal(input.getAttribute('aria-invalid'), 'true');
        assert.equal(input.dataset.feedbackError, undefined);
        assert.equal(nodes.size, 1);
        assert.equal(scope.children[0].removed, true);

        syncFields(scope, {});
        assert.equal(input.getAttribute('aria-invalid'), null);
    } finally {
        globalThis.document = originalDocument;
    }
});
