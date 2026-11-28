import assert from 'node:assert/strict';
import test from 'node:test';
import initConfirmDialogs, { confirmAction, confirmSubmit, optionsFrom } from '../../resources/js/confirm-dialog.js';

function page() {
    const node = () => ({ textContent: '', hidden: false, className: '', replaceChildren(...children) { this.children = children; } });
    const parts = { '[data-confirm-title]': node(), '[data-confirm-message]': node(), '[data-confirm-details]': node(), '[data-confirm-accept]': node(), '[data-confirm-review-field]': node(), '[data-confirm-review-checkbox]': node() };
    const listeners = [];
    const dialog = {
        open: false,
        returnValue: 'confirm',
        querySelector: selector => parts[selector],
        showModal() { this.open = true; },
        addEventListener: (name, listener) => listeners.push(listener),
        close(value) { this.open = false; this.returnValue = value; listeners.splice(0).forEach(listener => listener()); },
    };
    const root = { getElementById: id => (id === 'confirm-dialog' ? dialog : null), createElement: () => node() };
    return { root, dialog, parts };
}

function submission(root) {
    const form = { dataset: {}, ownerDocument: root, requestSubmit(submitter) { this.resubmittedWith = submitter; } };
    const event = {
        target: form, submitter: { name: 'action' }, defaultPrevented: false, stopped: false,
        preventDefault() { this.defaultPrevented = true; }, stopImmediatePropagation() { this.stopped = true; },
    };
    return { form, event };
}

test('the shared dialog shows escaped text, details and the danger action', async () => {
    const { root, dialog, parts } = page();

    const result = confirmAction({ title: 'Archive?', message: '<b>Moved</b>', details: [['Program', 'Annual aid']], confirmLabel: 'Archive', danger: true }, root);

    assert.equal(dialog.open, true);
    assert.equal(dialog.returnValue, '');
    assert.equal(parts['[data-confirm-title]'].textContent, 'Archive?');
    assert.equal(parts['[data-confirm-message]'].textContent, '<b>Moved</b>');
    assert.deepEqual(parts['[data-confirm-details]'].children.map(child => child.textContent), ['Program', 'Annual aid']);
    assert.equal(parts['[data-confirm-accept]'].className, 'danger-button');
    dialog.close('confirm');
    assert.equal(await result, true);
});

test('escape or cancel resolves false and a missing dialog fails closed', async () => {
    const { root, dialog } = page();
    const result = confirmAction({}, root);

    dialog.close('');

    assert.equal(await result, false);
    assert.equal(await confirmAction({}, { getElementById: () => null }), false);
});

test('confirmed submissions resubmit once with the original submitter', async () => {
    const { root, dialog } = page();
    const { form, event } = submission(root);

    confirmSubmit(event, { title: 'Queue SMS?' });
    assert.equal(event.defaultPrevented, true);
    assert.equal(event.stopped, true);
    dialog.close('confirm');
    await new Promise(setImmediate);

    assert.equal(form.resubmittedWith, event.submitter);
    const second = submission(root).event;
    second.target = form;
    confirmSubmit(second, {});
    assert.equal(second.defaultPrevented, false);
    assert.equal(form.dataset.confirmed, undefined);
});

test('cancelled submissions are not resubmitted', async () => {
    const { root, dialog } = page();
    const { form, event } = submission(root);

    confirmSubmit(event, {});
    dialog.close('cancel');
    await new Promise(setImmediate);

    assert.equal(form.resubmittedWith, undefined);
});

test('a delete button runs its action only after confirmation', async () => {
    const { root, dialog, parts } = page();
    const handlers = {};
    root.addEventListener = (type, handler) => { handlers[type] = handler; };
    initConfirmDialogs(root);
    let deletes = 0;
    const button = {
        dataset: { confirm: 'Delete this template?', confirmLabel: 'Delete', confirmTone: 'danger' },
        isConnected: true,
        click() {
            const event = submission(root).event;
            event.target = { closest: () => this };
            handlers.click(event);
            if (!event.stopped) deletes++;
        },
    };

    button.click();
    assert.equal(parts['[data-confirm-accept]'].textContent, 'Delete');
    assert.equal(deletes, 0);
    dialog.close('cancel');
    await new Promise(setImmediate);
    assert.equal(deletes, 0);

    button.click();
    dialog.close('confirm');
    await new Promise(setImmediate);
    assert.equal(deletes, 1);
});

test('declarative attributes map to dialog options', () => {
    const options = optionsFrom({ dataset: { confirm: 'Stops releases.', confirmTitle: 'Declare?', confirmLabel: 'Record', confirmTone: 'danger' } });

    assert.deepEqual(options, { title: 'Declare?', message: 'Stops releases.', confirmLabel: 'Record', danger: true });
});

test('review confirmation requires a fresh checkbox before proceeding', async () => {
    const { root, dialog, parts } = page();
    const result = confirmAction({ reviewRequired: true }, root);
    const checkbox = parts['[data-confirm-review-checkbox]'];
    const accept = parts['[data-confirm-accept]'];
    assert.equal(accept.disabled, true);
    checkbox.checked = true;
    checkbox.onchange();
    assert.equal(accept.disabled, false);
    dialog.close('confirm');
    assert.equal(await result, true);

    const second = confirmAction({ reviewRequired: true }, root);
    assert.equal(checkbox.checked, false);
    dialog.close('confirm');
    assert.equal(await second, false);
});

test('batch action review lists only selected records and their count', () => {
    const options = optionsFrom({
        hasAttribute: () => true, dataset: { confirmAction: 'Print selected IDs' },
        elements: [
            { name: 'senior_ids[]', checked: true, dataset: { seniorName: 'Synthetic One' } },
            { name: 'senior_ids[]', checked: false, dataset: { seniorName: 'Synthetic Two' } },
            { name: 'senior_ids[]', checked: true, disabled: true, dataset: { seniorName: 'Synthetic Unavailable' } },
        ],
    });
    assert.equal(options.title, 'Confirm Action');
    assert.equal(options.reviewRequired, true);
    assert.deepEqual(options.details, [['Action', 'Print selected IDs'], ['Selected records', '1'], ['Senior citizen', 'Synthetic One']]);
});
