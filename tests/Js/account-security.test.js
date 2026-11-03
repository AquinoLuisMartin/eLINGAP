import test from 'node:test';
import assert from 'node:assert/strict';
import initAccountSecurity, { clearCredentials } from '../../resources/js/account-security.js';

test('credential dismissal removes the value and reports that it cannot be retrieved', () => {
    const output = { textContent: 'synthetic secret' };
    const feedback = { textContent: '' };
    clearCredentials({ querySelectorAll: selector => selector === '[data-credential-value]' ? [output] : [feedback] });
    assert.equal(output.textContent, '');
    assert.equal(feedback.textContent, 'Credential hidden. It cannot be retrieved again.');
});

function page() {
    const output = { textContent: 'synthetic secret' };
    const feedback = { textContent: '' };
    const events = {};
    const clicks = {};
    const button = { addEventListener: (name, callback) => { clicks[name] = callback; } };
    const document = { querySelectorAll: selector => {
        if (selector === '[data-dismiss-credential]') return [button];
        if (selector === '[data-credential-value]') return [output];
        if (selector === '[data-credential-feedback]') return [feedback];
        throw new Error('Account security must not generate employee IDs from names.');
    } };
    const window = { addEventListener: (name, callback) => { events[name] = callback; } };
    initAccountSecurity(document, window);
    return { output, feedback, events, clicks };
}

test('dismissing the private handover clears credentials', () => {
    const { output, clicks } = page();
    clicks.click();
    assert.equal(output.textContent, '');
});

test('leaving the page clears credentials', () => {
    const { output, events } = page();
    events.pagehide();
    assert.equal(output.textContent, '');
});

test('returning from browser history clears cached credentials', () => {
    const { output, events } = page();
    events.pageshow({ persisted: true });
    assert.equal(output.textContent, '');
});

test('a fresh credential handover stays visible until dismissed or left', () => {
    const { output, events } = page();
    events.pageshow({ persisted: false });
    assert.equal(output.textContent, 'synthetic secret');
});
