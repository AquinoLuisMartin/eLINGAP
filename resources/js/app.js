// Landing page interactions

document.addEventListener('DOMContentLoaded', () => {
    initMobileMenu();
    initModal('concern');
    initModal('search');
    initConcernForm();
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

// Handle concern form submission
function initConcernForm() {
    const form = document.getElementById('concern-form');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        alert('Your concern has been submitted to the OSCA Santa Maria Helpdesk. We will contact you shortly.');
        closeModal(document.getElementById('concern-modal'));
        form.reset();
    });
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
