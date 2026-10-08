import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

// Landing page interactions

document.addEventListener('DOMContentLoaded', () => {
    if (!window.Alpine) {
        window.Alpine = Alpine;
        Alpine.plugin(collapse);
        Alpine.start();
    }

    initStaffControls();
    initApplicationWizard();
    initMobileMenu();
    initModal('search');
    initModal('login');
    initPasswordToggle();
    initBackToTop();
    initViewSwitcher();
    initEscapeClose();
});

function initStaffControls() {
    const sidebar = document.getElementById('staff-sidebar');
    const toggle = document.getElementById('staff-nav-toggle');
    toggle?.addEventListener('click', () => {
        const expanded = toggle.getAttribute('aria-expanded') === 'true';
        sidebar.classList.toggle('md:w-64', !expanded);
        sidebar.classList.toggle('md:w-20', expanded);
        sidebar.querySelectorAll('.staff-nav-label').forEach(label => label.hidden = expanded);
        toggle.setAttribute('aria-expanded', String(!expanded));
        toggle.setAttribute('aria-label', expanded ? 'Expand navigation' : 'Collapse navigation');
    });

    const claimantType = document.getElementById('claimant-type');
    const proxyFields = document.getElementById('proxy-fields');
    if (claimantType && proxyFields) {
        const sync = () => {
            proxyFields.hidden = claimantType.value !== 'PROXY';
            proxyFields.querySelectorAll('input[type="text"]').forEach(input => input.required = !proxyFields.hidden);
        };
        claimantType.addEventListener('change', sync);
        sync();
    }

    const releaseForm = document.getElementById('payout-release-form');
    releaseForm?.addEventListener('submit', event => {
        const claimant = claimantType.value === 'PROXY' ? releaseForm.elements.claimant_name.value : 'Senior in person';
        const facts = `${releaseForm.dataset.beneficiary}\n${releaseForm.dataset.program}\n${releaseForm.dataset.amount}\nClaimant: ${claimant}`;
        if (!window.confirm(`Confirm payout release?\n\n${facts}`)) event.preventDefault();
    });

    const broadcast = document.getElementById('broadcast-message');
    const count = document.getElementById('sms-count');
    if (broadcast && count) {
        document.getElementById('broadcast-template')?.addEventListener('change', event => {
            const selected = event.target.selectedOptions[0];
            if (selected?.dataset.body) {
                broadcast.value = selected.dataset.body;
                broadcast.dispatchEvent(new Event('input'));
            }
        });
        const basic = "@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ ÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
        const extended = '^{}\\[~]|€';
        const sync = () => {
            const chars = [...broadcast.value];
            const gsm = chars.every(char => basic.includes(char) || extended.includes(char));
            const units = gsm ? chars.reduce((n, char) => n + (extended.includes(char) ? 2 : 1), 0) : broadcast.value.length;
            const single = gsm ? 160 : 70;
            const multipart = gsm ? 153 : 67;
            const segments = units ? (units <= single ? 1 : Math.ceil(units / multipart)) : 0;
            count.textContent = `${chars.length} characters · ${gsm ? 'GSM-7' : 'Unicode'} · ${segments} segment${segments === 1 ? '' : 's'}`;
        };
        broadcast.addEventListener('input', sync);
        sync();
        document.getElementById('sms-broadcast-form')?.addEventListener('submit', event => {
            const form = event.currentTarget;
            const facts = `${form.dataset.audience}\nEligible: ${form.dataset.eligible}\nExcluded: ${form.dataset.excluded}\n${count.textContent}\n\n${broadcast.value}`;
            if (!window.confirm(`Queue this SMS broadcast?\n\n${facts}`)) event.preventDefault();
        });
    }
}

function initApplicationWizard() {
    const form = document.getElementById('application-wizard');
    if (!form) return;
    const steps = [...form.querySelectorAll('[data-wizard-step]')];
    const back = document.getElementById('wizard-back');
    const next = document.getElementById('wizard-next');
    const submit = document.getElementById('wizard-submit');
    const progress = document.getElementById('wizard-progress');
    const labels = ['Senior citizen', 'Program and date', 'Review and submit'];
    let index = 0;
    const show = () => {
        steps.forEach((step, position) => { step.hidden = position !== index; });
        back.hidden = index === 0;
        next.hidden = index === steps.length - 1;
        submit.hidden = index !== steps.length - 1;
        progress.textContent = `Step ${index + 1} of ${steps.length} · ${labels[index]}`;
        if (index === 2) {
            const senior = form.elements.senior_citizen_id.selectedOptions[0]?.textContent || '';
            const program = form.elements.program_id.selectedOptions[0]?.textContent || '';
            document.getElementById('application-review').textContent = `${senior} · ${program} · ${form.elements.applied_on.value}`;
        }
    };
    next.addEventListener('click', () => {
        const controls = [...steps[index].querySelectorAll('input, select, textarea')];
        if (controls.every(control => control.reportValidity())) { index++; show(); }
    });
    back.addEventListener('click', () => { index--; show(); });
    show();
}

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
