import assert from 'node:assert/strict';
import test from 'node:test';
import broadcastComposer, { broadcastGuidance, templateCopy } from '../../resources/js/broadcast-composer.js';

function composer(options = {}) {
    const state = broadcastComposer(options);
    let opened = 0;
    let closed = 0;
    let updates = 0;
    state.$refs = {
        templateDialog: { showModal() { opened++; }, close() { closed++; } },
        message: { dispatchEvent(event) { assert.equal(event.type, 'input'); updates++; } },
    };
    state.$nextTick = callback => callback();
    return { state, opened: () => opened, closed: () => closed, updates: () => updates };
}

function selection(value, body) {
    return { target: { value, selectedOptions: [{ dataset: { body } }] } };
}

test('existing templates fill the composer with saved copy and all guidance placeholders', () => {
    const { state, updates } = composer();

    state.chooseTemplate(selection('1', 'OSCA: Pension reminder.'));

    assert.equal(state.broadcastBody, `OSCA: Pension reminder.\n\n${broadcastGuidance}`);
    assert.equal(updates(), 1);
    assert.equal(state.previousTemplate, '1');
});

test('placeholder selection preserves a message already being edited', () => {
    const { state, updates } = composer({ message: 'Draft in progress.' });

    state.chooseTemplate(selection('', undefined));

    assert.equal(state.broadcastBody, 'Draft in progress.');
    assert.equal(updates(), 0);
});

test('template selection updates the composer when the dropdown has its own message state', () => {
    const { state } = composer();
    const dropdown = { message: '' };
    const scopes = [dropdown, state];
    const scope = new Proxy({}, {
        get: (_, key) => scopes.find(item => Object.hasOwn(item, key))?.[key],
        set: (_, key, value) => { (scopes.find(item => Object.hasOwn(item, key)) ?? state)[key] = value; return true; },
    });

    state.chooseTemplate.call(scope, selection('1', 'Saved message'));

    assert.equal(state.broadcastBody, `Saved message\n\n${broadcastGuidance}`);
    assert.equal(dropdown.message, '');
});

test('template guidance does not duplicate placeholders or exceed the message limit', () => {
    assert.equal(templateCopy(broadcastGuidance), broadcastGuidance);
    const copy = templateCopy('A'.repeat(1600));
    assert.equal(copy.length, 1600);
    assert.ok(copy.endsWith(broadcastGuidance));
});

test('creating a template opens the editor without replacing the existing message', () => {
    const { state, opened } = composer({ message: 'Existing draft' });
    state.previousTemplate = '2';

    state.chooseTemplate(selection('__new__'));

    assert.equal(opened(), 1);
    assert.equal(state.selectedTemplate, '2');
    assert.equal(state.broadcastBody, 'Existing draft');
    assert.equal(state.templateBody, broadcastGuidance);
});

test('valid templates stay in component state, populate the message, and can be selected again', () => {
    const { state, closed, updates } = composer();
    state.templateTitle = '  Office notice  ';
    state.templateBody = '  Confirmed local message  ';

    state.saveTemplate();
    state.chooseTemplate(selection('local-1', state.localTemplates[0].body));

    assert.deepEqual(state.localTemplates, [{ id: 'local-1', title: 'Office notice', body: 'Confirmed local message' }]);
    assert.equal(state.selectedTemplate, 'local-1');
    assert.equal(state.broadcastBody, 'Confirmed local message');
    assert.equal(closed(), 1);
    assert.equal(updates(), 2);
    assert.deepEqual(composer().state.localTemplates, []);
});

test('empty and oversized local templates do not save or close the editor', () => {
    for (const [title, body] of [[' ', 'Message'], ['Title', ' '], ['A'.repeat(121), 'Message'], ['Title', 'A'.repeat(1601)]]) {
        const { state, closed } = composer();
        state.templateTitle = title;
        state.templateBody = body;

        state.saveTemplate();

        assert.equal(state.localTemplates.length, 0);
        assert.notEqual(state.templateError, '');
        assert.equal(closed(), 0);
    }
});

test('program mock counts update from the current barangay base and All Programs restores it', () => {
    const { state } = composer({ eligible: 200 });
    for (const [program, count] of [['sss', 50], ['gsis', 30], ['lgu', 80], ['centenarian', 4], ['all', 200]]) {
        state.program = program;
        assert.equal(state.eligibleRecipients, count);
    }
    const empty = composer({ eligible: 0 }).state;
    empty.program = 'sss';
    assert.equal(empty.eligibleRecipients, 0);
});
