export function workspaceTheme(browser = window) {
    return {
        theme: browser.document.documentElement.classList.contains('theme-dark') ? 'dark' : 'light',
        init() {
            this.applyTheme(this.theme, false);
        },
        applyTheme(value, persist = true) {
            this.theme = value === 'dark' ? 'dark' : 'light';
            const dark = this.theme === 'dark';
            const { document } = browser;
            document.documentElement.classList.toggle('theme-dark', dark);
            document.documentElement.classList.toggle('theme-light', !dark);
            document.documentElement.style.colorScheme = this.theme;
            document.body.classList.toggle('theme-dark', dark);
            if (persist) {
                try { browser.localStorage.setItem('elingap-theme', this.theme); } catch {}
            }
        },
        toggleTheme() {
            this.applyTheme(this.theme === 'dark' ? 'light' : 'dark');
        },
        syncTheme(event) {
            if (event.key === 'elingap-theme') this.applyTheme(event.newValue, false);
        },
    };
}
