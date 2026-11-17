export default function initLoginTurnstile(doc = document, browser = window) {
    const container = doc.getElementById('login-turnstile');
    if (!container) return;

    const modal = doc.getElementById('login-modal');
    const form = doc.getElementById('login-form');
    const status = doc.getElementById('login-verification-status');
    const retry = doc.getElementById('login-verification-retry');
    let widgetId;
    let script;
    const showError = () => {
        status.textContent = 'Security verification could not complete. Please retry.';
        retry.hidden = false;
    };
    const render = () => {
        if (modal.classList.contains('hidden') || widgetId !== undefined) return;
        widgetId = browser.turnstile.render(container, {
            sitekey: container.dataset.sitekey,
            action: 'login',
            size: 'flexible',
            callback: () => {
                status.textContent = 'Security verification complete.';
                retry.hidden = true;
            },
            'expired-callback': () => {
                status.textContent = 'Verification expired. Please verify again.';
                browser.turnstile.reset(widgetId);
            },
            'error-callback': () => { showError(); return true; },
            'timeout-callback': showError,
        });
    };
    const load = () => {
        if (!container.dataset.sitekey) { showError(); return; }
        if (browser.turnstile) { render(); return; }
        if (script) return;
        script = doc.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.defer = true;
        script.onload = render;
        script.onerror = () => { script.remove(); script = undefined; showError(); };
        doc.head.append(script);
    };

    modal.addEventListener('login:opened', load);
    retry.addEventListener('click', () => {
        retry.hidden = true;
        status.textContent = 'Loading security verification...';
        if (widgetId !== undefined) browser.turnstile.reset(widgetId);
        else load();
    });
    form.addEventListener('submit', event => {
        if (widgetId === undefined || !browser.turnstile.getResponse(widgetId)) {
            event.preventDefault();
            status.textContent = 'Complete the security verification before signing in.';
            retry.hidden = false;
        }
    });
    browser.addEventListener('pageshow', event => {
        if (event.persisted && widgetId !== undefined) browser.turnstile.reset(widgetId);
    });
    if (!modal.classList.contains('hidden')) load();
}
