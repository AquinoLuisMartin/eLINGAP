import assert from 'node:assert/strict';
import test from 'node:test';
import { workspaceSidebar } from '../../resources/js/workspace-sidebar.js';

function fixture({ storage = new Map(), desktop = true, hover = false, blocked = false } = {}) {
    const inside = { matches: () => false };
    const sidebar = { contains: element => element === inside, matches: () => hover };
    const browser = {
        document: { getElementById: () => sidebar, activeElement: null },
        matchMedia: () => ({ matches: desktop }),
        sessionStorage: {
            getItem(key) { if (blocked) throw new Error('Blocked'); return storage.get(key) ?? null; },
            setItem(key, value) { if (blocked) throw new Error('Blocked'); storage.set(key, value); },
        },
    };
    const state = workspaceSidebar({}, browser);
    const watchers = {};
    state.$watch = (name, callback) => { watchers[name] = callback; };
    state.initSidebar();
    return { state, browser, inside, change(name, value) { state[name] = value; watchers[name]?.(value); } };
}

test('new routes start collapsed outside the sidebar despite stale saved expansion', () => {
    const storage = new Map([['elingap-admin-navigation-expanded', 'true']]);
    const first = fixture({ storage, hover: true });
    assert.equal(first.state.sidebarOpen, true);
    first.change('adminOpen', false);
    const next = fixture({ storage });
    assert.equal(next.state.sidebarOpen, false);
    assert.equal(next.state.adminOpen, false);
    next.change('sidebarOpen', false);
    assert.equal(fixture({ storage }).state.sidebarOpen, false);
});

test('hover opens and mouse leave collapses even when a clicked navigation link has focus', () => {
    const context = fixture();
    context.state.enterSidebar();
    assert.equal(context.state.sidebarOpen, true);
    context.browser.document.activeElement = context.inside;
    context.state.focusSidebar({ target: context.inside });
    context.state.leaveSidebar();
    assert.equal(context.state.sidebarOpen, false);
});

test('keyboard navigation stays accessible until focus leaves the sidebar', () => {
    const context = fixture({ hover: true });
    context.state.trackSidebarPointer({ target: context.inside });
    assert.equal(context.state.sidebarOpen, true);
    context.browser.document.activeElement = context.inside;
    context.inside.matches = () => true;
    context.state.focusSidebar({ target: context.inside });
    context.state.trackSidebarPointer({ target: {} });
    assert.equal(context.state.sidebarOpen, true);
    context.browser.document.activeElement = null;
    context.state.trackSidebarPointer({ target: {} });
    assert.equal(context.state.sidebarOpen, false);
});

test('keyboard focus leaving the sidebar collapses it when the cursor is outside', () => {
    const context = fixture();
    context.inside.matches = () => true;
    context.state.focusSidebar({ target: context.inside });
    assert.equal(context.state.sidebarOpen, true);
    context.state.blurSidebar({ relatedTarget: {} });
    assert.equal(context.state.sidebarOpen, false);
    assert.equal(context.state.sidebarKeyboardFocus, false);
});

test('mobile navigation never restores an open drawer or persists it to another route', () => {
    const storage = new Map([['elingap-admin-navigation-expanded', 'true']]);
    const context = fixture({ storage, desktop: false });
    assert.equal(context.state.sidebarOpen, false);
    context.change('sidebarOpen', true);
    assert.equal(fixture({ storage, desktop: false }).state.sidebarOpen, false);
    context.state.syncSidebarViewport();
    assert.equal(context.state.sidebarOpen, false);
});

test('blocked storage preserves desktop hover and viewport behavior', () => {
    const context = fixture({ hover: true, blocked: true });
    assert.equal(context.state.sidebarOpen, true);
    assert.equal(context.state.adminOpen, true);
    assert.doesNotThrow(() => context.change('sidebarOpen', false));
    context.state.syncSidebarViewport();
    assert.equal(context.state.sidebarOpen, true);
});
