import test from 'node:test';
import assert from 'node:assert/strict';
import { clearCredentials, usernameSuggestions } from '../../resources/js/account-security.js';

test('credential dismissal removes the value and reports that it cannot be retrieved', () => {
    const output = { textContent: 'synthetic secret' };
    const feedback = { textContent: '' };
    clearCredentials({ querySelectorAll: selector => selector === '[data-credential-value]' ? [output] : [feedback] });
    assert.equal(output.textContent, '');
    assert.equal(feedback.textContent, 'Credential hidden. It cannot be retrieved again.');
});

test('manual username edits stop automatic suggestions until explicitly requested', async () => {
    const values = [];
    const requests = [];
    const service = usernameSuggestions({ fetcher: async (url, request) => {
        requests.push(request);
        return { ok: true, json: async () => ({ username: 'synthetic.staff' }) };
    }, apply: value => values.push(value), report: () => {}, token: 'synthetic-csrf', endpoint: '/suggestion' });
    service.edited();
    await service.suggest('Synthetic', 'Staff');
    assert.equal(requests.length, 0);
    await service.suggest('Synthetic', 'Staff', true);
    assert.deepEqual(values, ['synthetic.staff']);
    assert.equal(requests[0].method, 'POST');
    assert.equal(requests[0].headers['X-CSRF-TOKEN'], 'synthetic-csrf');
});

test('out-of-order suggestion responses cannot replace the most recent name', async () => {
    const requests = [];
    const values = [];
    const service = usernameSuggestions({ fetcher: () => new Promise(resolve => requests.push(resolve)),
        apply: value => values.push(value), report: () => {}, token: 'synthetic-csrf', endpoint: '/suggestion' });
    const first = service.suggest('Synthetic', 'First');
    const second = service.suggest('Synthetic', 'Second');
    requests[1]({ ok: true, json: async () => ({ username: 'synthetic.second' }) });
    await second;
    requests[0]({ ok: true, json: async () => ({ username: 'synthetic.first' }) });
    await first;
    assert.deepEqual(values, ['synthetic.second']);
});

test('manual editing while a suggestion is pending cannot overwrite the chosen username', async () => {
    let finish;
    const values = [];
    const service = usernameSuggestions({ fetcher: () => new Promise(resolve => { finish = resolve; }),
        apply: value => values.push(value), report: () => {}, token: 'synthetic-csrf', endpoint: '/suggestion' });
    const pending = service.suggest('Synthetic', 'Staff');
    service.edited();
    finish({ ok: true, json: async () => ({ username: 'synthetic.staff' }) });
    await pending;
    assert.deepEqual(values, []);
});
