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

test('required markers share the label text wrapper instead of becoming a separate field row', () => {
    const originalDocument = globalThis.document;
    function element() {
        return {
            children: [], dataset: {}, parent: null,
            get firstChild() { return this.children[0] || null; },
            contains(node) { return this === node || this.children.some(child => child.contains(node)); },
            append(child) {
                child.remove();
                child.parent = this;
                this.children.push(child);
            },
            insertBefore(child, before) {
                child.parent = this;
                this.children.splice(this.children.indexOf(before), 0, child);
            },
            remove() {
                if (this.parent) this.parent.children.splice(this.parent.children.indexOf(this), 1);
                this.parent = null;
            },
            setAttribute() {},
            querySelector(selector) {
                const nodes = this.children.flatMap(child => [child, ...(child.children || [])]);
                if (selector === '[data-required-marker]') return nodes.find(node => 'requiredMarker' in node.dataset) || null;
                if (selector === '.field-required') return nodes.find(node => node.className === 'field-required') || null;
                return nodes.find(node => node.className === 'field-label-text') || null;
            },
        };
    }
    const label = element();
    const text = Object.assign(element(), { textContent: 'Date of death' });
    const control = Object.assign(element(), {
        attributes: [], name: 'died_on', type: 'date', required: true, disabled: false, labels: [label],
        getAttribute: () => null, hasAttribute: () => false, closest: () => null,
    });
    label.append(text);
    label.append(control);
    globalThis.document = { getElementById: () => null, createElement: element };
    const scope = { children: [], querySelectorAll: () => [control], hasAttribute: () => false };

    try {
        syncFields(scope);
        const wrapper = label.firstChild;
        const marker = wrapper.children[1];
        assert.equal(wrapper.className, 'field-label-text');
        assert.equal(wrapper.firstChild, text);
        assert.equal(marker.textContent, '*');
        assert.equal(label.children[1], control);
        syncFields(scope);
        assert.deepEqual(wrapper.children, [text, marker]);

        control.disabled = true;
        syncFields(scope);
        assert.deepEqual(wrapper.children, [text]);
        control.disabled = false;
        syncFields(scope);
        assert.equal(label.firstChild, wrapper);
        assert.equal(wrapper.children[1].textContent, '*');
        assert.equal(label.children.length, 2);

        const externalLabel = element();
        externalLabel.append(Object.assign(element(), { textContent: 'Supporting document' }));
        control.labels = [externalLabel];
        syncFields(scope);
        assert.equal(externalLabel.children.length, 1);
        assert.equal(externalLabel.firstChild.className, 'field-label-text');
        assert.equal(externalLabel.firstChild.children[1].textContent, '*');
    } finally {
        globalThis.document = originalDocument;
    }
});

test('required radio results share one group marker instead of marking each choice', () => {
    const originalDocument = globalThis.document;
    const legend = {
        marker: null,
        querySelector(selector) { return selector.includes('required') ? this.marker : null; },
        append(marker) { this.marker = marker; },
    };
    const group = { querySelector: () => legend };
    const controls = ['PRINTED', 'NOT_PRINTED'].map(value => ({
        attributes: [], dataset: {}, type: 'radio', name: 'printing-result-10', value, required: true,
        labels: [{ querySelector() { assert.fail('The result label must not receive a required marker.'); } }],
        getAttribute: () => null, hasAttribute: () => false,
        closest: selector => selector === '[data-required-group]' ? group : null,
    }));
    globalThis.document = { getElementById: () => null, createElement: () => ({ dataset: {}, setAttribute() {} }) };
    try {
        const scope = { children: [], querySelectorAll: () => controls, hasAttribute: () => false };
        syncFields(scope);
        const marker = legend.marker;
        assert.equal(marker.textContent, '*');
        syncFields(scope);
        assert.equal(legend.marker, marker);
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
