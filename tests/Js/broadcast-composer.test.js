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

test('complete program advisories copy verbatim without appending another generic message', () => {
    const body = 'OSCA | Synthetic program\nPetsa: [Petsa]\nOras: [Oras]\nLugar: [Lugar/Venue]\nDalhin ang OSCA ID. Dumating sa oras.';
    const { state } = composer();
    state.chooseTemplate(selection('12', body));
    assert.equal(state.broadcastBody, body);
    assert.equal(templateCopy(body), body);
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
    const id = state.localTemplates[0].id;
    state.chooseTemplate(selection(id, state.localTemplates[0].body));

    assert.deepEqual(state.localTemplates, [{ id, title: 'Office notice', body: 'Confirmed local message', active: true, programId: null }]);
    assert.equal(state.selectedTemplate, id);
    assert.equal(state.broadcastBody, 'Confirmed local message');
    assert.equal(closed(), 1);
    assert.equal(updates(), 2);
    assert.deepEqual(composer().state.localTemplates, []);
});

test('program changes select a matching template, fill its saved body, and refresh the audience from the root scope', () => {
    const { state } = composer();
    const watchers = {};
    const templates = [
        { id: '1', title: 'Program A - Schedule', body: 'Program A message', active: true, programId: 10 },
        { id: '2', title: 'Program B - Schedule', body: 'Program B message', active: true, programId: 20 },
    ];
    state.$el = { dataset: { templates: JSON.stringify(templates) } };
    state.$watch = (name, callback) => { watchers[name] = callback; };
    let refreshed = 0;
    state.refreshAudience = () => { refreshed++; };
    state.$refs.confirmed = { checked: true };
    state.init();
    assert.equal(state.selectedTemplate, '');

    state.program = '20';
    watchers.program();
    assert.equal(state.selectedTemplate, '2');
    assert.equal(state.broadcastBody, 'Program B message');
    assert.equal(state.$refs.confirmed.checked, false);
    assert.equal(refreshed, 1);
    assert.deepEqual(state.programTemplates().map(template => template.id), ['2']);
    state.program = '30';
    watchers.program();
    assert.equal(state.selectedTemplate, '');
    assert.equal(state.broadcastBody, '');
    assert.deepEqual(state.programTemplates(), []);
    state.barangay = '4';
    watchers.barangay();
    assert.equal(refreshed, 3);
});

test('program matching handles initial selections, edited seed templates, and program-specific local templates', () => {
    const { state } = composer({ program: '10' });
    state.seedTemplates = [{ id: '1', title: 'Program A - Schedule', body: 'Original', active: true, programId: 10 }];
    state.overrides = { '1': { id: '1', title: 'Edited title', body: 'Edited message', active: true } };
    state.matchProgramTemplate();
    assert.equal(state.selectedTemplate, '1');
    assert.equal(state.broadcastBody, 'Edited message');
    state.templateTitle = 'Local reminder';
    state.templateBody = 'Program A reminder';
    state.saveTemplate();
    assert.equal(state.localTemplates[0].programId, '10');
    state.program = '20';
    assert.deepEqual(state.programTemplates(), []);
    state.program = '';
    assert.equal(state.programTemplates().length, 2);
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

test('audience counts use the server and keep the current selection when responses arrive out of order', async (context) => {
    context.mock.method(globalThis, 'fetch', async () => ({ ok: true, json: async () => ({ eligible: 7, excluded: 2 }) }));
    const originalWindow = globalThis.window;
    globalThis.window = { location: { origin: 'https://preview.test' } };
    context.after(() => { globalThis.window = originalWindow; });
    const { state } = composer({ eligible: 200, audienceUrl: '/messaging' });
    state.program = '12';
    assert.equal(state.audiencePreview, true);
    await state.refreshAudience();
    assert.equal(state.eligibleRecipients, 7);
    assert.equal(state.excludedRecipients, 2);
    assert.equal(state.audiencePreview, false);
    assert.equal(new URL(fetch.mock.calls[0].arguments[0]).searchParams.get('program_id'), '12');

    let finishFirst;
    fetch.mock.mockImplementationOnce(() => new Promise(resolve => { finishFirst = resolve; }));
    state.program = '13';
    const first = state.refreshAudience();
    state.program = '14';
    await state.refreshAudience();
    finishFirst({ ok: true, json: async () => ({ eligible: 99, excluded: 0 }) });
    await first;
    assert.equal(state.appliedProgram, '14');
    assert.equal(state.eligibleRecipients, 7);

    fetch.mock.mockImplementationOnce(async () => ({ ok: false }));
    state.program = '15';
    await state.refreshAudience();
    assert.equal(state.audiencePreview, true);
    assert.notEqual(state.audienceError, '');
    await state.refreshAudience();
    assert.equal(state.audiencePreview, false);
});
