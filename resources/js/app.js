import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import idImageEditor from './id-image-editor';
import initApplicationWizard from './application-wizard';
import { formatSeniorMobile } from './senior-registration-input';
import uiSelect from './select-control';
import initFormFeedback from './form-feedback';
import initAccountSecurity from './account-security';

window.idImageEditor = idImageEditor;
window.formatSeniorMobile = formatSeniorMobile;
window.uiSelect = uiSelect;
const modalTriggers = new WeakMap();

// Landing page interactions

document.addEventListener('DOMContentLoaded', () => {
    if (!window.Alpine) {
        window.Alpine = Alpine;
        Alpine.plugin(collapse);
        Alpine.start();
    }

    initStaffControls();
    initApplicationWizard(document.getElementById('application-wizard'));
    initMobileMenu();
    initModal('search');
    initModal('login');
    initPasswordToggle();
    initLoginSubmission();
    initBackToTop();
    initViewSwitcher();
    initEscapeClose();
    initFormFeedback();
    initAccountSecurity(document, window);
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

    menu.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        closeMobileMenu(menu, bars, close, btn);
        btn.focus();
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
    modal.addEventListener('keydown', event => {
        if (event.key !== 'Tab') return;
        const controls = [...modal.querySelectorAll('button, input:not([type="hidden"]), select, textarea, a[href], [tabindex]')]
            .filter(control => !control.disabled && control.tabIndex >= 0 && control.getClientRects().length);
        const first = controls[0];
        const last = controls[controls.length - 1];
        if (!first) { event.preventDefault(); modal.focus(); return; }
        if (event.shiftKey && (document.activeElement === first || !modal.contains(document.activeElement))) {
            event.preventDefault(); last.focus();
        } else if (!event.shiftKey && (document.activeElement === last || !modal.contains(document.activeElement))) {
            event.preventDefault(); first.focus();
        }
    });
    if (!modal.classList.contains('hidden')) openModal(modal, focusTarget);
}

function openModal(modal, focusTarget) {
    // Close mobile menu if open
    const menu = document.getElementById('mobile-menu');
    if (menu) closeMobileMenu(menu, document.getElementById('menu-icon-bars'), document.getElementById('menu-icon-close'), document.getElementById('mobile-menu-btn'));

    modalTriggers.set(modal, document.activeElement);
    modal.dataset.bodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    modal.classList.remove('hidden');
    requestAnimationFrame(() => {
        if (!modal.classList.contains('hidden')) (focusTarget || modal).focus();
    });
}

function closeModal(modal) {
    modal.classList.add('hidden');
    document.body.style.overflow = modal.dataset.bodyOverflow || '';
    modalTriggers.get(modal)?.focus();
}

function initLoginSubmission() {
    const form = document.getElementById('login-form');
    if (!form) return;
    const button = document.getElementById('login-submit');
    const setBusy = busy => {
        button.disabled = busy;
        form.setAttribute('aria-busy', String(busy));
        button.querySelector('[data-login-label]').classList.toggle('hidden', busy);
        const loading = button.querySelector('[data-login-loading]');
        loading.classList.toggle('hidden', !busy);
        loading.classList.toggle('inline-flex', busy);
    };
    form.addEventListener('submit', event => {
        if (button.disabled) { event.preventDefault(); return; }
        setBusy(true);
    });
    window.addEventListener('pageshow', () => setBusy(false));
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
            window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
        });
    });
}

function initViewSwitcher() {
    const landingView = document.getElementById('landing-view');
    const aboutView = document.getElementById('about-view');
    const links = document.querySelectorAll('[data-view]');
    const viewLinks = document.querySelectorAll('[data-view-link]');

    if (!landingView || !aboutView) return;

    const setView = (view, scroll = true) => {
        const showingAbout = view === 'about';
        landingView.classList.toggle('hidden', showingAbout);
        aboutView.classList.toggle('hidden', !showingAbout);

        viewLinks.forEach(link => {
            if (link.dataset.view === view) link.setAttribute('aria-current', 'page');
            else link.removeAttribute('aria-current');
        });

        if (scroll) {
            const heading = (showingAbout ? aboutView : landingView).querySelector('h1');
            heading.setAttribute('tabindex', '-1');
            heading.focus({ preventScroll: true });
            window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
        }
    };

    links.forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            setView(link.dataset.view);
        });
    });

    const scrollToSection = (target, focus = true) => {
        if (landingView.contains(target)) setView('home', false);
        if (focus) {
            target.setAttribute('tabindex', '-1');
            target.focus({ preventScroll: true });
        }
        target.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
    };

    document.querySelectorAll('.landing-page a[href^="#"]:not([data-view])').forEach(link => {
        link.addEventListener('click', event => {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
            const target = document.getElementById(link.hash.slice(1));
            if (!target) return;
            event.preventDefault();
            scrollToSection(target);
        });
    });

    const initialTarget = document.getElementById(window.location.hash.slice(1));
    setView(initialTarget === aboutView ? 'about' : 'home', false);
    if (window.location.hash) {
        window.history.replaceState(window.history.state, '', window.location.pathname + window.location.search);
        if (initialTarget) requestAnimationFrame(() => scrollToSection(initialTarget, false));
    }
}

// Close all modals on Escape key
function initEscapeClose() {
    window.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('[role="dialog"]:not(.hidden)').forEach(modal => {
            if (modalTriggers.has(modal)) closeModal(modal);
        });
    });
}
