import assert from 'node:assert/strict';
import test from 'node:test';
import initLoginTurnstile from '../../resources/js/login-turnstile.js';

function setup(hidden = true) {
    const elements = {};
    for (const id of ['login-turnstile', 'login-modal', 'login-form', 'login-verification-status', 'login-verification-retry']) {
        elements[id] = Object.assign(new EventTarget(), { dataset: {}, hidden: true, textContent: '' });
    }
    elements['login-turnstile'].dataset.sitekey = 'synthetic-site';
    elements['login-modal'].classList = { contains: () => hidden };
    const scripts = [];
    const doc = {
        getElementById: id => elements[id],
        createElement: () => ({ remove() { this.removed = true; } }),
        head: { append: script => scripts.push(script) },
    };
    const browser = new EventTarget();
    const renders = [];
    const resets = [];
    let token = '';
    const ready = () => {
        browser.turnstile = {
            render(container, options) { renders.push({ container, options }); return 'widget-1'; },
            reset(id) { resets.push(id); token = ''; },
            getResponse: () => token,
        };
        scripts.at(-1).onload();
    };
    const open = () => {
        hidden = false;
        elements['login-modal'].dispatchEvent(new Event('login:opened'));
    };
    initLoginTurnstile(doc, browser);
    return { elements, scripts, browser, renders, resets, ready, open, setToken(value) { token = value; } };
}

test('opening the login dialog loads one script and renders one widget with the login action', () => {
    const state = setup();
    assert.equal(state.scripts.length, 0);

    state.open();
    state.open();
    assert.equal(state.scripts.length, 1);
    assert.equal(state.scripts[0].src, 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit');
    state.ready();
    state.open();

    assert.equal(state.renders.length, 1);
    assert.equal(state.renders[0].options.action, 'login');
    assert.equal(state.renders[0].options.sitekey, 'synthetic-site');
});

test('login submissions require a verified token and permit submission after verification', () => {
    const state = setup(false);
    const submit = () => {
        const event = new Event('submit', { cancelable: true });
        state.elements['login-form'].dispatchEvent(event);
        return event.defaultPrevented;
    };
    assert.equal(submit(), true);
    state.ready();
    assert.equal(submit(), true);

    state.setToken('synthetic-token');
    state.renders[0].options.callback();

    assert.equal(submit(), false);
    assert.equal(state.elements['login-verification-retry'].hidden, true);
    assert.equal(state.elements['login-verification-status'].textContent, 'Security verification complete.');
});

test('failed script loading can be retried without leaving duplicate scripts', () => {
    const state = setup(false);
    const failed = state.scripts[0];

    failed.onerror();
    assert.equal(failed.removed, true);
    assert.equal(state.elements['login-verification-retry'].hidden, false);
    state.elements['login-verification-retry'].dispatchEvent(new Event('click'));
    state.ready();

    assert.equal(state.scripts.length, 2);
    assert.equal(state.renders.length, 1);
});

test('challenge errors offer retry and expiry and history restoration reset consumed tokens', () => {
    const state = setup(false);
    state.ready();
    const options = state.renders[0].options;

    options['error-callback']();
    assert.equal(state.elements['login-verification-retry'].hidden, false);
    state.elements['login-verification-retry'].dispatchEvent(new Event('click'));
    options['expired-callback']();
    options['timeout-callback']();
    assert.equal(state.elements['login-verification-retry'].hidden, false);
    state.browser.dispatchEvent(Object.assign(new Event('pageshow'), { persisted: true }));

    assert.deepEqual(state.resets, ['widget-1', 'widget-1', 'widget-1']);
});

test('missing site configuration blocks submission and does not load an unusable widget', () => {
    const state = setup();
    state.elements['login-turnstile'].dataset.sitekey = '';

    state.open();

    assert.equal(state.scripts.length, 0);
    assert.equal(state.elements['login-verification-retry'].hidden, false);
});
