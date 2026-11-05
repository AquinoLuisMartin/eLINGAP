import assert from 'node:assert/strict';
import test from 'node:test';
import templateState, { readTemplates } from '../../resources/js/sms-template-state.js';
import smsTemplateEditor from '../../resources/js/sms-template-editor.js';
import broadcastComposer from '../../resources/js/broadcast-composer.js';
import smsMessages, { smsStatusClass } from '../../resources/js/sms-messages.js';

const sample = { id: '1', title: 'Pension reminder', body: 'Original notice', active: true };

function browser(t) {
    const previous = globalThis.window;
    const values = new Map();
    const target = new EventTarget();
    target.localStorage = { getItem: key => values.get(key) ?? null, setItem: (key, value) => values.set(key, value) };
    globalThis.window = target;
    t.after(() => { if (previous === undefined) delete globalThis.window; else globalThis.window = previous; });
    return { target, values };
}

function initialize(state, seeds = [sample]) {
    state.$el = { dataset: { templates: JSON.stringify(seeds) } };
    state.$nextTick = callback => callback();
    state.init();
    return state;
}

test('template edits update title, body, and active status in the list and broadcast dropdown', t => {
    const { target } = browser(t);
    const editor = initialize(smsTemplateEditor({ key: 'user-1' }));
    const composer = initialize(broadcastComposer({ key: 'user-1' }));
    let closed = 0;
    editor.$refs = { editDialog: { showModal() {}, close() { closed++; } } };
    target.addEventListener('sms-templates-updated', event => composer.refreshTemplates(event));

    editor.editTemplate(editor.displayedTemplates[0]);
    editor.draft.title = 'Updated title';
    editor.draft.body = 'Updated message';
    assert.equal(editor.displayedTemplates[0].title, sample.title);
    editor.saveTemplate();

    assert.equal(editor.displayedTemplates[0].title, 'Updated title');
    assert.equal(composer.availableTemplates()[0].body, 'Updated message');
    assert.equal(closed, 1);
    editor.editTemplate(editor.displayedTemplates[0]);
    editor.draft.active = false;
    editor.saveTemplate();
    assert.equal(editor.displayedTemplates[0].active, false);
    assert.deepEqual(composer.availableTemplates(), []);

    editor.draft.active = true;
    editor.saveTemplate();
    assert.equal(composer.availableTemplates()[0].title, 'Updated title');
});

test('browser persistence restores edited templates across navigation and isolates users', t => {
    browser(t);
    const state = initialize(templateState('user-1'));
    state.persistTemplate({ ...sample, title: 'Saved title', active: false });

    const restored = initialize(templateState('user-1'));
    const anotherUser = initialize(templateState('user-2'));

    assert.deepEqual(restored.availableTemplates(), []);
    assert.equal(restored.overrides['1'].title, 'Saved title');
    assert.deepEqual(anotherUser.availableTemplates(), [sample]);
});

test('storage changes from another tab sync inactive templates absent from server options', t => {
    const { values } = browser(t);
    const composer = initialize(broadcastComposer({ key: 'user-1' }), []);
    values.set('user-1', JSON.stringify({ '1': sample }));

    composer.refreshTemplates({ key: 'user-1' });

    assert.deepEqual(composer.availableTemplates(), [sample]);
    values.set('user-1', '{}');
    composer.refreshTemplates({ key: 'unrelated' });
    assert.equal(composer.availableTemplates().length, 1);
    composer.refreshTemplates({ key: 'user-1' });
    assert.deepEqual(composer.availableTemplates(), []);
});

test('malformed stored data and unavailable storage do not break editing', t => {
    const { target, values } = browser(t);
    values.set('user-1', 'not JSON');
    assert.deepEqual(readTemplates('user-1'), {});
    values.set('user-1', JSON.stringify({ bad: { ...sample, id: 'bad', body: 42 }, '1': sample }));
    assert.deepEqual(readTemplates('user-1'), { '1': sample });
    target.localStorage.setItem = () => { throw new Error('Storage disabled'); };
    const state = initialize(templateState('user-1'));

    state.persistTemplate({ ...sample, title: 'Memory edit' });

    assert.equal(state.availableTemplates()[0].title, 'Memory edit');
});

test('invalid edits do not overwrite stored templates', t => {
    browser(t);
    const editor = initialize(smsTemplateEditor({ key: 'user-1' }));
    editor.$refs = { editDialog: { showModal() {}, close() { assert.fail('Invalid edit closed the dialog'); } } };
    editor.editTemplate(sample);
    editor.draft.title = ' ';

    editor.saveTemplate();

    assert.notEqual(editor.error, '');
    assert.deepEqual(editor.displayedTemplates, [sample]);
    assert.deepEqual(readTemplates('user-1'), {});
});

test('both audience filters immediately recalculate eligible and excluded counters', () => {
    const composer = broadcastComposer({ eligible: 200, excluded: 40, barangayIds: ['11', '22'] });
    assert.equal(composer.broadcastBody, '');
    assert.equal(composer.audiencePreview, false);
    assert.equal(composer.excludedRecipients, 40);

    composer.barangay = '11';
    assert.equal(composer.eligibleRecipients, 67);
    assert.equal(composer.excludedRecipients, 173);
    assert.equal(composer.audiencePreview, true);
    composer.program = 'lgu';
    assert.equal(composer.eligibleRecipients, 27);
    assert.equal(composer.excludedRecipients, 213);
    composer.barangay = '';
    composer.program = 'all';
    assert.equal(composer.eligibleRecipients, 200);
    assert.equal(composer.excludedRecipients, 40);
    assert.equal(composer.audiencePreview, false);
});

test('retry confirmation changes only the selected failed row and preserves other rows', () => {
    const state = smsMessages();
    state.$nextTick = callback => callback();
    let closed = 0;
    state.$refs = { retryDialog: { showModal() {}, close() { closed++; } }, messageDialog: { showModal() {} } };
    const details = { id: 1, recipient: 'Synthetic recipient', mobile: '09XX XXX 0001', status: 'Failed', failureReason: 'Inbox full' };

    state.openRetry(details);
    assert.equal(state.rowStatus(1, 'Failed'), 'Failed');
    state.confirmRetry();
    state.confirmRetry();

    assert.equal(state.rowStatus(1, 'Failed'), 'Queued');
    assert.equal(state.rowStatus(2, 'Failed'), 'Failed');
    assert.equal(closed, 1);
    assert.match(state.notice, /No SMS was sent/);
    state.openDetails(details);
    assert.equal(state.selected.status, 'Queued');
    assert.match(smsStatusClass('Failed'), /bg-red-100 text-red-700/);
    assert.match(smsStatusClass('Sent'), /bg-green-100 text-green-700/);
});

test('sent rows cannot open retry and cancel leaves the row unchanged', () => {
    const state = smsMessages();
    state.$nextTick = callback => callback();
    let opened = 0;
    state.$refs = { retryDialog: { showModal() { opened++; } } };
    state.openRetry({ id: 1, status: 'Sent' });
    assert.equal(opened, 0);
    state.openRetry({ id: 2, status: 'Failed' });
    state.retry = null;
    state.confirmRetry();
    assert.equal(state.rowStatus(2, 'Failed'), 'Failed');
});

test('broadcast resend keeps OSCA and failure details and queues only its own frontend row', () => {
    const state = smsMessages({ action: 'Resend' });
    state.$nextTick = callback => callback();
    state.$refs = { retryDialog: { showModal() {}, close() {} }, messageDialog: { showModal() {} } };
    const details = { id: 7, oscaId: 'MOCK-ID-7', recipient: 'Synthetic recipient', mobile: '09XX XXX 0007', status: 'Failed', failureReason: 'SMS Gateway error', body: 'Synthetic broadcast' };

    state.openDetails(details);
    assert.equal(state.selected.failureReason, 'SMS Gateway error');
    state.openRetry(details);
    assert.equal(state.retry.oscaId, 'MOCK-ID-7');
    assert.equal(state.retry.mobile, '09XX XXX 0007');
    state.confirmRetry();

    assert.equal(state.rowStatus(7, 'Failed'), 'Queued');
    assert.match(state.notice, /^Resend queued/);
    assert.equal(state.rowStatus(8, 'Failed'), 'Failed');
});
