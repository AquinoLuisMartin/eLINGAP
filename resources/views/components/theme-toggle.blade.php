<button type="button" class="workspace-theme-toggle" @click="toggleTheme()" :aria-label="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'" :title="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'" :aria-pressed="theme === 'dark'">
    <span x-show="theme === 'light'" aria-hidden="true"><x-admin.icon name="sun" size="18" /></span>
    <span x-show="theme === 'dark'" x-cloak aria-hidden="true"><x-admin.icon name="moon" size="18" /></span>
    <span x-text="theme === 'dark' ? 'Dark mode' : 'Light mode'">Light mode</span>
</button>
