import assert from 'node:assert/strict';
import test from 'node:test';
import { menuBounds, menuPosition, showMenu, hideMenu, anchoredDropdown } from '../../resources/js/menu-position.js';

test('menus stay inside the main content area without covering an expanded sidebar', () => {
    const bounds = { left: 252, top: 0, right: 1280, bottom: 720 };
    const position = menuPosition({ top: 200, bottom: 244, left: 280, width: 700 }, 1280, 720, 304, { bounds });
    assert.equal(position.left, '280px');
    assert.equal(position.width, '700px');
    assert.equal(position.top, '250px');
    const wide = menuPosition({ top: 200, bottom: 244, left: 260, width: 1300 }, 1280, 720, 304, { bounds });
    assert.equal(wide.left, '260px');
    assert.equal(wide.width, '1012px');
});

test('right aligned account menus fit narrow screens and flip above when necessary', () => {
    const position = menuPosition({ top: 500, bottom: 544, left: 300, right: 332, width: 32 }, 360, 640, 400,
        { align: 'end', width: 350, maxHeight: 500 });
    assert.equal(position.width, '344px');
    assert.equal(position.left, '8px');
    assert.equal(position.top, 'auto');
    assert.equal(position.bottom, '146px');
});

test('mobile keyboard viewport offsets and modal boundaries constrain positioning', () => {
    globalThis.window = { innerWidth: 400, innerHeight: 800, visualViewport: { offsetLeft: 0, offsetTop: 90, width: 400, height: 300 } };
    globalThis.document = { documentElement: { clientWidth: 385 } };
    const bounds = menuBounds({ closest: () => ({ getBoundingClientRect: () => ({ left: 20, right: 380, top: 40, bottom: 750 }) }) });
    assert.deepEqual(bounds, { left: 20, right: 380, top: 90, bottom: 390 });
    const position = menuPosition({ top: 300, bottom: 344, left: 30, width: 340 }, 400, 800, 304, { bounds });
    assert.equal(position.top, 'auto');
    assert.equal(position.bottom, '506px');
    assert.equal(position.maxHeight, '196px');
});

test('tiny viewports never produce negative menu dimensions', () => {
    const position = menuPosition({ top: 0, bottom: 44, left: 0, width: 400 }, 10, 20);
    assert.equal(position.width, '0px');
    assert.equal(position.maxHeight, '0px');
});

test('native popovers open in the top layer without changing their DOM parent', () => {
    let open = false;
    const attributes = {};
    const menu = { setAttribute: (key, value) => { attributes[key] = value; }, matches: () => open,
        showPopover: () => { open = true; }, hidePopover: () => { open = false; } };
    showMenu(menu);
    assert.equal(attributes.popover, 'manual');
    assert.equal(open, true);
    hideMenu(menu);
    assert.equal(open, false);
    assert.doesNotThrow(() => showMenu({}));
});

test('closing an account menu before the next tick prevents a delayed reopening', () => {
    let nextTick;
    const state = anchoredDropdown();
    state.$nextTick = callback => { nextTick = callback; };
    state.$el = {};
    state.show();
    state.close();
    assert.doesNotThrow(() => nextTick());
    assert.deepEqual(state.position, {});
});
