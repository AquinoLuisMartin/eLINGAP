export function beginSubmission(form, event, pending) {
    if (event.defaultPrevented || form.method.toLowerCase() !== 'post' || form.target || form.id === 'login-form'
        || form.dataset.submitFeedback === 'off' || Array.from(form.attributes).some(attribute => attribute.name.startsWith('wire:submit'))) return;

    if (pending.has(form)) {
        event.preventDefault();
        return;
    }

    const controls = Array.from(form.querySelectorAll('button:not([type]), button[type="submit"], input[type="submit"]'))
        .filter(control => !control.disabled);
    const status = form.ownerDocument.createElement('p');
    status.className = 'ui-submit-status';
    status.setAttribute('role', 'status');
    status.textContent = 'Submitting...';
    form.append(status);
    const previousBusy = form.getAttribute('aria-busy');
    const states = controls.map(control => ({ control, ariaDisabled: control.getAttribute('aria-disabled') }));
    form.setAttribute('aria-busy', 'true');
    for (const { control } of states) {
        control.setAttribute('aria-disabled', 'true');
        control.setAttribute('data-ui-busy', '');
    }
    pending.set(form, () => {
        if (previousBusy === null) form.removeAttribute('aria-busy');
        else form.setAttribute('aria-busy', previousBusy);
        for (const { control, ariaDisabled } of states) {
            control.removeAttribute('data-ui-busy');
            if (ariaDisabled === null) control.removeAttribute('aria-disabled');
            else control.setAttribute('aria-disabled', ariaDisabled);
        }
        status.remove();
    });
}

export default function initFormFeedback() {
    const pending = new Map();
    document.addEventListener('submit', event => {
        if (event.target.tagName === 'FORM') beginSubmission(event.target, event, pending);
    });
    window.addEventListener('pageshow', () => {
        for (const restore of pending.values()) restore();
        pending.clear();
    });
}
