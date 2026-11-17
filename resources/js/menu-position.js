export function menuBounds(trigger) {
    const viewport = window.visualViewport;
    const width = Math.min(window.innerWidth, document.documentElement?.clientWidth || window.innerWidth);
    const left = viewport?.offsetLeft ?? 0;
    const top = viewport?.offsetTop ?? 0;
    const bounds = { left, top, right: Math.min(width, left + (viewport?.width ?? width)), bottom: top + (viewport?.height ?? window.innerHeight) };
    const container = trigger.closest?.('dialog, [role="dialog"]') ?? trigger.closest?.('.admin-main');
    const rect = container?.getBoundingClientRect();
    if (rect) {
        bounds.left = Math.max(bounds.left, rect.left);
        bounds.right = Math.min(bounds.right, rect.right);
        bounds.top = Math.max(bounds.top, rect.top);
        bounds.bottom = Math.min(bounds.bottom, rect.bottom);
    }
    return bounds;
}

export function menuPosition(rect, width, height, contentHeight = 304, options = {}) {
    const gap = options.gap ?? 6;
    const margin = 8;
    const bounds = options.bounds ?? { left: 0, top: 0, right: width, bottom: height };
    const limit = options.maxHeight ?? 304;
    const below = Math.max(0, bounds.bottom - rect.bottom - gap - margin);
    const above = Math.max(0, rect.top - bounds.top - gap - margin);
    const upwards = below < Math.min(limit, contentHeight) && above > below;
    const menuHeight = Math.min(limit, upwards ? above : below);
    const menuWidth = Math.max(0, Math.min(options.width ?? rect.width, bounds.right - bounds.left - margin * 2));
    const left = options.align === 'end' ? (rect.right ?? rect.left + rect.width) - menuWidth : rect.left;
    return {
        left: `${Math.max(bounds.left + margin, Math.min(left, bounds.right - menuWidth - margin))}px`,
        top: upwards ? 'auto' : `${rect.bottom + gap}px`,
        bottom: upwards ? `${height - rect.top + gap}px` : 'auto',
        width: `${menuWidth}px`,
        maxHeight: `${menuHeight}px`,
    };
}

export function showMenu(menu) {
    if (typeof menu?.showPopover !== 'function') return;
    menu.setAttribute('popover', 'manual');
    if (!menu.matches(':popover-open')) menu.showPopover();
}

export function hideMenu(menu) {
    if (typeof menu?.hidePopover === 'function' && menu.matches(':popover-open')) menu.hidePopover();
}

export function trackMenu(update) {
    let active = true;
    let frame;
    const tick = () => {
        if (!active) return;
        update();
        if (active) frame = window.requestAnimationFrame?.(tick);
    };
    frame = window.requestAnimationFrame?.(tick);
    return () => {
        active = false;
        if (frame !== undefined) window.cancelAnimationFrame?.(frame);
    };
}

export function anchoredDropdown({ width = 350, align = 'end' } = {}) {
    let stopTracking;
    let opened = false;
    return {
        position: {},
        show() {
            opened = true;
            stopTracking?.();
            const update = () => {
                const trigger = this.$el.previousElementSibling;
                const rect = trigger.getBoundingClientRect();
                const position = { ...menuPosition(rect, window.innerWidth, window.innerHeight, this.$el.scrollHeight, {
                    bounds: menuBounds(trigger), align, width, maxHeight: Infinity, gap: 8,
                }), position: 'fixed', right: 'auto', margin: '0' };
                if (JSON.stringify(position) !== JSON.stringify(this.position)) this.position = position;
            };
            this.$nextTick(() => {
                if (!opened) return;
                update();
                showMenu(this.$el);
                stopTracking = trackMenu(update);
            });
        },
        close() {
            opened = false;
            stopTracking?.();
            hideMenu(this.$el);
        },
        destroy() { this.close(); },
    };
}
