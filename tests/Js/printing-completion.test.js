import assert from 'node:assert/strict';
import test from 'node:test';
import initPrintingCompletion, { printingResponse } from '../../resources/js/printing-completion.js';

function page() {
    const node = () => ({
        dataset: {}, children: [], listeners: {}, disabled: false,
        append(...children) { this.children.push(...children); },
        replaceChildren(...children) { this.children = children; },
        addEventListener(name, listener) { this.listeners[name] = listener; },
        showModal() { this.open = true; }, close() { this.open = false; },
    });
    const rows = node();
    const inputs = () => rows.children.flatMap(group => group.children.at(-1).children.map(label => label.children[0]));
    const save = node();
    const later = node();
    const form = node();
    form.querySelector = () => ({ value: 'synthetic-csrf' });
    form.querySelectorAll = () => inputs().filter(input => input.checked);
    const dialog = node();
    dialog.dataset = { pendingUrl: '/pending', completeUrl: '/complete' };
    dialog.querySelector = selector => ({ '[data-print-results]': form, '[data-print-result-rows]': rows, '[data-print-save]': save, '[data-print-later]': later })[selector];
    const notices = Object.fromEntries(['title', 'message', 'details', 'accept', 'review-field', 'review-checkbox'].map(key => [`[data-confirm-${key}]`, node()]));
    const popup = node();
    popup.querySelector = selector => notices[selector];
    popup.close = () => { popup.open = false; popup.returnValue = 'confirm'; popup.listeners.close(); };
    const viewer = { location: {}, close() { this.closed = true; } };
    const root = node();
    root.createElement = node;
    root.createTextNode = text => ({ textContent: text });
    root.getElementById = id => id === 'printing-completion' ? dialog : popup;
    root.querySelector = selector => selector.includes('csrf-token') ? { content: 'synthetic-csrf' } : null;
    const dispatches = [];
    root.defaultView = { listeners: {}, addEventListener(name, listener) { this.listeners[name] = listener; },
        open: () => viewer, setTimeout() {}, Livewire: { dispatch: name => dispatches.push(name) } };
    rows.ownerDocument = root;
    const button = node();
    button.isConnected = true;
    const printForm = { action: '/print', querySelectorAll: () => [button], matches: () => true };
    const event = target => ({ target, preventDefault() { this.defaultPrevented = true; }, stopImmediatePropagation() {} });
    return { root, dialog, form, save, later, inputs, viewer, popup, notices, dispatches, button, printForm, event };
}

function mockRequests(t, handler) {
    t.mock.method(globalThis, 'fetch', handler);
    const original = globalThis.FormData;
    globalThis.FormData = class { constructor(form) { this.form = form; } };
    t.after(() => { globalThis.FormData = original; });
}

test('a generated PDF opens completion without saving a print status; each batch record needs a choice', async t => {
    const ui = page();
    const posted = [];
    mockRequests(t, async (url, options) => {
        if (url === '/print') return new Response('synthetic-pdf', { headers: { 'Content-Type': 'application/pdf', 'X-ID-Print-Attempts': '[10,11]' } });
        if (url === '/pending') return Response.json({ attempts: [
            { id: 10, name: '<script>Synthetic One</script>', osca_id: 'SYN-1' },
            { id: 11, name: 'Synthetic Two', osca_id: 'SYN-2' },
        ] });
        posted.push(JSON.parse(options.body));
        return Response.json({ message: 'Saved synthetic results.' });
    });
    initPrintingCompletion(ui.root);
    await ui.root.listeners.submit(ui.event(ui.printForm));
    assert.equal(ui.dialog.open, true);
    assert.equal(ui.viewer.location.href.startsWith('blob:'), true);
    assert.deepEqual(posted, []);
    assert.equal(ui.save.disabled, true);
    const inputs = ui.inputs();
    assert.equal(inputs.length, 4);
    const firstCard = ui.dialog.querySelector('[data-print-result-rows]').children[0];
    assert.equal(firstCard.children[1].children[0].textContent, '<script>Synthetic One</script>');
    assert.equal(firstCard.children[1].children[1].textContent, 'OSCA ID SYN-1');
    assert.equal(inputs[0].required, true);
    assert.equal(inputs[1].required, true);
    assert.equal(inputs[0].name, inputs[1].name);
    assert.notEqual(inputs[0].name, inputs[2].name);
    inputs[0].checked = true;
    ui.form.listeners.change();
    assert.equal(ui.save.disabled, true);
    inputs[3].checked = true;
    ui.form.listeners.change();
    assert.equal(ui.save.disabled, false);
    const saving = ui.form.listeners.submit(ui.event(ui.form));
    await new Promise(setImmediate);
    assert.deepEqual(posted, [{ results: [{ attempt_id: 10, result: 'PRINTED' }, { attempt_id: 11, result: 'NOT_PRINTED' }] }]);
    assert.equal(ui.notices['[data-confirm-title]'].textContent, 'Printing Results Saved');
    ui.popup.close();
    await saving;
    assert.ok(ui.dispatches.includes('id-print-updated'));
});

test('blocked popups never start generation and report the error in a popup', async t => {
    const ui = page();
    ui.root.defaultView.open = () => null;
    mockRequests(t, () => { assert.fail('Generation must not start without a PDF window.'); });
    initPrintingCompletion(ui.root);
    const submitting = ui.root.listeners.submit(ui.event(ui.printForm));
    assert.equal(ui.popup.open, true);
    assert.equal(ui.notices['[data-confirm-title]'].textContent, 'Printing Error');
    ui.popup.close();
    await submitting;
});

test('later preserves unresolved results without posting a completion', async t => {
    const ui = page();
    const requests = [];
    mockRequests(t, async url => {
        requests.push(url);
        return Response.json({ attempts: [{ id: 10, name: 'Synthetic One', osca_id: 'SYN-1' }] });
    });
    initPrintingCompletion(ui.root);
    await ui.root.defaultView.listeners['id-print-completion']();
    ui.later.listeners.click();
    assert.equal(ui.dialog.open, false);
    assert.deepEqual(requests, ['/pending']);
});

test('loading or refreshing a printing workspace does not automatically open completion', async t => {
    const ui = page();
    ui.root.querySelector = () => ({ dataset: { printWorkspace: '' } });
    const requests = [];
    mockRequests(t, async url => {
        requests.push(url);
        return Response.json({ attempts: [{ id: 10, name: 'Synthetic One', osca_id: 'SYN-1' }] });
    });

    initPrintingCompletion(ui.root);
    await new Promise(setImmediate);

    assert.deepEqual(requests, []);
    assert.notEqual(ui.dialog.open, true);
    assert.notEqual(ui.popup.open, true);
});

test('printing errors use server validation messages without treating the request as successful', async () => {
    await assert.rejects(printingResponse(Response.json({ errors: { printing: ['Reprinting requires approval.'] } }, { status: 422 })), /Reprinting requires approval/);
});
