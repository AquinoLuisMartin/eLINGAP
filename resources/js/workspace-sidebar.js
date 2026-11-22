export function workspaceSidebar({ id = 'admin-navigation', key = 'elingap-admin-navigation' } = {}, browser = window) {
    const desktop = () => browser.matchMedia('(min-width: 761px)').matches;
    const read = name => {
        try { return browser.sessionStorage.getItem(`${key}-${name}`); } catch { return null; }
    };
    const write = (name, value) => {
        try { browser.sessionStorage.setItem(`${key}-${name}`, String(value)); } catch {}
    };
    return {
        sidebarOpen: false,
        sidebarTrigger: null,
        sidebarKeyboardFocus: false,
        adminOpen: true,
        initSidebar() {
            const sidebar = browser.document.getElementById(id);
            this.sidebarOpen = desktop() && !!sidebar?.matches(':hover');
            this.adminOpen = read('group') !== 'false';
            this.$watch('adminOpen', value => write('group', value));
        },
        enterSidebar() {
            if (!desktop()) return;
            this.sidebarKeyboardFocus = false;
            this.sidebarOpen = true;
        },
        leaveSidebar() {
            if (desktop() && !this.sidebarKeyboardFocus) this.sidebarOpen = false;
        },
        focusSidebar(event) {
            this.sidebarKeyboardFocus = event.target.matches(':focus-visible');
            this.sidebarOpen = true;
        },
        blurSidebar(event) {
            const sidebar = browser.document.getElementById(id);
            if (sidebar?.contains(event.relatedTarget)) return;
            this.sidebarKeyboardFocus = false;
            if (desktop() && !sidebar?.matches(':hover')) this.sidebarOpen = false;
        },
        trackSidebarPointer(event) {
            if (!desktop() || !this.sidebarOpen) return;
            const sidebar = browser.document.getElementById(id);
            if (sidebar && !sidebar.contains(event.target) && !(this.sidebarKeyboardFocus && sidebar.contains(browser.document.activeElement))) this.sidebarOpen = false;
        },
        syncSidebarViewport() {
            const sidebar = browser.document.getElementById(id);
            this.sidebarOpen = desktop() && !!sidebar && (sidebar.matches(':hover') || (this.sidebarKeyboardFocus && sidebar.contains(browser.document.activeElement)));
        },
    };
}
