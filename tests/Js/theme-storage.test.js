import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const root = new URL('../../', import.meta.url);

for (const [page, shell] of [
    ['resources/views/layouts/app.blade.php', 'resources/views/layouts/app.blade.php'],
    ['resources/views/administration/dashboard.blade.php', 'resources/views/administration/dashboard/panel.blade.php'],
]) {
    test(`${shell} initializes and switches theme when browser storage is blocked`, () => {
        const classes = new Set();
        const classList = {
            add: value => classes.add(value),
            contains: value => classes.has(value),
            toggle(value, enabled) { if (enabled) classes.add(value); else classes.delete(value); },
        };
        const context = vm.createContext({
            document: { documentElement: { classList, style: {} }, body: { classList } },
            localStorage: {
                getItem() { throw new Error('Storage unavailable'); },
                setItem() { throw new Error('Storage unavailable'); },
            },
        });
        const prepaint = readFileSync(new URL(page, root), 'utf8').match(/<script>([\s\S]*?)<\/script>/)[1];
        vm.runInContext(prepaint, context);
        const data = readFileSync(new URL(shell, root), 'utf8').match(/x-data="([\s\S]*?)"/)[1];
        const state = vm.runInContext(`(${data})`, context);
        state.init?.();
        assert.equal(state.theme, 'light');
        state.toggleTheme();
        assert.equal(state.theme, 'dark');
        assert.equal(context.document.documentElement.style.colorScheme, 'dark');
        assert.equal(classes.has('theme-dark'), true);
    });
}
