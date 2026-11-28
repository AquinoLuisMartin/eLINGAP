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
    const printForm = { action: '/print', dataset: {}, elements: { namedItem: () => ({ value: 'a4' }) }, querySelectorAll: () => [button], matches: () => true };
    const event = target => ({ target, preventDefault() { this.defaultPrevented = true; }, stopImmediatePropagation() {} });
    return { root, dialog, form, save, later, inputs, viewer, popup, notices, dispatches, button, printForm, event };
}

test('a generated PDF opens completion without saving a print status; each batch record needs a choice', async t => {
    const ui = page();
    const posted = [];
    t.mock.method(globalThis, 'fetch', async (url, options) => {
        if (url === '/print') {
            assert.deepEqual(JSON.parse(options.body), { paper: 'a4' });
            return new Response('synthetic-pdf', { headers: { 'Content-Type': 'application/pdf', 'X-ID-Print-Attempts': '[10,11]' } });
        }
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

function batchForm(form, layout, rows) {
    form.dataset.idPrint = 'batch';
    const fields = { paper: { value: 'a4' }, layout: { value: layout }, reviewed: { checked: true } };
    form.elements = Object.assign(rows, { namedItem: name => fields[name] });
}

test('batch generation sends only checked, enabled row IDs as a JSON array', async t => {
    const ui = page();
    batchForm(ui.printForm, 'batch', [
        { name: 'senior_ids[]', value: '', dataset: { seniorId: '42' }, checked: true },
        { name: 'senior_ids[]', dataset: { seniorId: '43' }, checked: false },
        { name: 'senior_ids[]', dataset: { seniorId: '44' }, checked: true, disabled: true },
        { name: 'senior_ids[]', dataset: { seniorId: '45' }, checked: true },
    ]);
    const sent = [];
    t.mock.method(globalThis, 'fetch', async (url, options) => {
        if (url === '/pending') return Response.json({ attempts: [] });
        sent.push(JSON.parse(options.body));
        assert.equal(options.headers['Content-Type'], 'application/json');
        assert.equal(options.headers['X-CSRF-TOKEN'], 'synthetic-csrf');
        return new Response('synthetic-pdf', { headers: { 'Content-Type': 'application/pdf', 'X-ID-Print-Attempts': '[10,11]' } });
    });
    initPrintingCompletion(ui.root);

    await ui.root.listeners.submit(ui.event(ui.printForm));

    assert.deepEqual(sent, [{ paper: 'a4', layout: 'batch', reviewed: true, senior_ids: ['42', '45'] }]);
    assert.equal(ui.viewer.location.href.startsWith('blob:'), true);
});

test('an invalid selection stops before opening a viewer or sending a printing request', async t => {
    t.mock.method(globalThis, 'fetch', () => { assert.fail('Invalid selections cannot generate a PDF.'); });
    for (const [layout, count] of [['batch', 0], ['batch', 5], ['single', 2]]) {
        const ui = page();
        const rows = Array.from({ length: count }, (_, index) => ({ name: 'senior_ids[]', dataset: { seniorId: String(index + 1) }, checked: true }));
        batchForm(ui.printForm, layout, rows);
        ui.root.defaultView.open = () => assert.fail('Invalid selections cannot open a viewer.');
        initPrintingCompletion(ui.root);

        const submitting = ui.root.listeners.submit(ui.event(ui.printForm));

        assert.match(ui.notices['[data-confirm-message]'].textContent, /Select (exactly one|one to four) ID/);
        ui.popup.close();
        await submitting;
    }
});

test('a row without its registration ID is rejected before submitting', async t => {
    const ui = page();
    batchForm(ui.printForm, 'single', [{ name: 'senior_ids[]', dataset: {}, value: '', checked: true }]);
    ui.root.defaultView.open = () => assert.fail('A missing ID cannot open a viewer.');
    t.mock.method(globalThis, 'fetch', () => assert.fail('A missing ID cannot be sent.'));
    initPrintingCompletion(ui.root);

    const submitting = ui.root.listeners.submit(ui.event(ui.printForm));
    assert.match(ui.notices['[data-confirm-message]'].textContent, /no valid registration ID/);
    ui.popup.close();
    await submitting;
});

test('blocked popups never start generation and report the error in a popup', async t => {
    const ui = page();
    ui.root.defaultView.open = () => null;
    t.mock.method(globalThis, 'fetch', () => { assert.fail('Generation must not start without a PDF window.'); });
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
    t.mock.method(globalThis, 'fetch', async url => {
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
    t.mock.method(globalThis, 'fetch', async url => {
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
