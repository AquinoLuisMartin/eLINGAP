import assert from 'node:assert/strict';
import test from 'node:test';
import { workspaceTheme } from '../../resources/js/workspace-theme.js';

function environment() {
    const classes = () => ({
        values: new Set(),
        contains(value) { return this.values.has(value); },
        toggle(value, enabled) { enabled ? this.values.add(value) : this.values.delete(value); },
    });
    return {
        document: { documentElement: { classList: classes(), style: {} }, body: { classList: classes() } },
        localStorage: { setItem(key, value) { this[key] = value; } },
    };
}

test('theme toggles consistently and persists the selected mode', () => {
    const browser = environment();
    const theme = workspaceTheme(browser);
    theme.init();
    theme.toggleTheme();
    assert.equal(theme.theme, 'dark');
    assert.equal(browser.document.documentElement.style.colorScheme, 'dark');
    assert.equal(browser.document.documentElement.classList.contains('theme-light'), false);
    assert.equal(browser.document.body.classList.contains('theme-dark'), true);
    assert.equal(browser.localStorage['elingap-theme'], 'dark');
    theme.toggleTheme();
    assert.equal(theme.theme, 'light');
    assert.equal(browser.document.body.classList.contains('theme-dark'), false);
    assert.equal(browser.localStorage['elingap-theme'], 'light');
});

test('saved theme initializes and synchronizes without writing storage again', () => {
    const browser = environment();
    browser.document.documentElement.classList.toggle('theme-dark', true);
    browser.localStorage.setItem = () => { throw new Error('Storage unavailable'); };
    const theme = workspaceTheme(browser);
    theme.init();
    assert.equal(theme.theme, 'dark');
    theme.syncTheme({ key: 'unrelated', newValue: 'light' });
    assert.equal(theme.theme, 'dark');
    theme.syncTheme({ key: 'elingap-theme', newValue: 'light' });
    assert.equal(theme.theme, 'light');
    theme.toggleTheme();
    assert.equal(theme.theme, 'dark');
});
