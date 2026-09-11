// Landing page interactions

document.addEventListener('DOMContentLoaded', () => {
    initMobileMenu();
    initModal('search');
    initModal('login');
    initPasswordToggle();
    initBackToTop();
    initViewSwitcher();
    initEscapeClose();
});

// Toggle mobile navigation drawer
function initMobileMenu() {
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');
    const bars = document.getElementById('menu-icon-bars');
    const close = document.getElementById('menu-icon-close');

    if (!btn || !menu) return;

    btn.addEventListener('click', () => {
        const opening = menu.classList.toggle('hidden');
        bars.classList.toggle('hidden', !opening);
        close.classList.toggle('hidden', opening);
        btn.setAttribute('aria-expanded', String(!opening));
    });

    menu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => closeMobileMenu(menu, bars, close, btn));
    });
}

function closeMobileMenu(menu, bars, close, btn) {
    menu.classList.add('hidden');
    bars.classList.remove('hidden');
    close.classList.add('hidden');
    btn.setAttribute('aria-expanded', 'false');
}

// Reusable modal open/close wiring by prefix
function initModal(prefix) {
    const modal = document.getElementById(prefix + '-modal');
    if (!modal) return;

    const openers = document.querySelectorAll('[data-open-modal="' + prefix + '"]');
    const closers = document.querySelectorAll('[data-close-modal="' + prefix + '"]');
    const focusTarget = modal.querySelector('[data-focus]');

    openers.forEach(btn => btn.addEventListener('click', () => openModal(modal, focusTarget)));
    closers.forEach(btn => btn.addEventListener('click', () => closeModal(modal)));

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal(modal);
    });
}

function openModal(modal, focusTarget) {
    // Close mobile menu if open
    const menu = document.getElementById('mobile-menu');
    if (menu) menu.classList.add('hidden');

    modal.classList.remove('hidden');
    if (focusTarget) setTimeout(() => focusTarget.focus(), 50);
}

function closeModal(modal) {
    modal.classList.add('hidden');
}

function initPasswordToggle() {
    document.querySelectorAll('[data-toggle-password]').forEach(toggle => {
        const input = document.getElementById(toggle.dataset.togglePassword);
        if (!input) return;

        toggle.addEventListener('click', () => {
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            toggle.setAttribute('aria-pressed', String(!showing));
        });
    });
}

function initBackToTop() {
    document.querySelectorAll('[data-back-to-top]').forEach(button => {
        button.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
}

function initViewSwitcher() {
    const landingView = document.getElementById('landing-view');
    const aboutView = document.getElementById('about-view');
    const links = document.querySelectorAll('[data-view]');
    const viewLinks = document.querySelectorAll('[data-view-link]');

    if (!landingView || !aboutView) return;

    const setView = (view) => {
        const showingAbout = view === 'about';
        landingView.classList.toggle('hidden', showingAbout);
        aboutView.classList.toggle('hidden', !showingAbout);

        viewLinks.forEach(link => {
            const active = link.dataset.view === view;
            link.classList.toggle('text-osca-primary', active);
            link.classList.toggle('font-semibold', active);
            link.classList.toggle('border-b-2', active);
            link.classList.toggle('border-osca-primary', active);
            link.classList.toggle('bg-osca-muted', active);
        });

        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    links.forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            setView(link.dataset.view);
        });
    });

    setView('home');
}

// Close all modals on Escape key
function initEscapeClose() {
    window.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('[role="dialog"]:not(.hidden)').forEach(modal => {
            modal.classList.add('hidden');
        });
    });
}
