import assert from 'node:assert/strict';
import test from 'node:test';
import broadcastComposer from '../../resources/js/broadcast-composer.js';

function composer(options = {}) {
    const state = broadcastComposer(options);
    let updates = 0;
    state.$refs = {
        message: { dispatchEvent(event) { assert.equal(event.type, 'input'); updates++; } },
    };
    state.$nextTick = callback => callback();
    return { state, updates: () => updates };
}

test('quick template selection replaces the draft with the exact saved body', () => {
    const { state, updates } = composer();
    const bodies = [
        'Short notice', '  Preserve whitespace\n\nAnd line breaks.  ', 'A'.repeat(1600),
        'OSCA | Synthetic program\nPetsa: [Petsa]\nOras: [Oras]\nLugar: [Lugar/Venue]',
    ];
    state.seedTemplates = bodies.map((body, index) => ({ id: String(index + 1), body, active: true }));

    for (const template of state.seedTemplates) {
        state.chooseTemplate(template.id);
        assert.equal(state.broadcastBody, template.body);
    }

    assert.equal(updates(), bodies.length);
});

test('placeholder selection preserves a message already being edited', () => {
    const { state, updates } = composer({ message: 'Draft in progress.' });

    state.chooseTemplate('');

    assert.equal(state.broadcastBody, 'Draft in progress.');
    assert.equal(updates(), 0);
});

test('template selection updates the composer when the dropdown has its own message state', () => {
    const { state } = composer();
    state.seedTemplates = [{ id: '1', body: 'Saved message', active: true }];
    const dropdown = { message: '' };
    const scopes = [dropdown, state];
    const scope = new Proxy({}, {
        get: (_, key) => scopes.find(item => Object.hasOwn(item, key))?.[key],
        set: (_, key, value) => { (scopes.find(item => Object.hasOwn(item, key)) ?? state)[key] = value; return true; },
    });

    state.chooseTemplate.call(scope, '1');

    assert.equal(state.broadcastBody, 'Saved message');
    assert.equal(dropdown.message, '');
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
    state.overrides = {
        '1': { id: '1', title: 'Edited title', body: 'Edited message', active: true },
        'local-2': { id: 'local-2', title: 'Local reminder', body: 'Program A reminder', active: true, programId: '10' },
    };
    state.matchProgramTemplate();
    assert.equal(state.selectedTemplate, '1');
    assert.equal(state.broadcastBody, 'Edited message');
    state.chooseTemplate('local-2');
    assert.equal(state.broadcastBody, 'Program A reminder');
    state.program = '20';
    assert.deepEqual(state.programTemplates(), []);
    state.program = '';
    assert.equal(state.programTemplates().length, 2);
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
