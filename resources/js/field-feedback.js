const controls = 'input:not([type="hidden"]):not([type="submit"]):not([type="button"]), select, textarea';
let sequence = 0;
const clientErrors = new WeakMap();

export function fieldName(control) {
    const binding = Array.from(control.attributes).find(attribute => /^wire:model(?:\.|$)/.test(attribute.name));
    return binding?.value || control.dataset.validationField || (control.name || '').replace(/\[([^\]]*)\]/g, (_, key) => '.' + (key || '*'));
}

export function fieldMessage(errors, name) {
    const key = Object.keys(errors).find(key => key === name || (name.endsWith('.*') && (key === name.slice(0, -2) || key.startsWith(name.slice(0, -1)))));
    const messages = errors[key];
    return Array.isArray(messages) ? messages[0] : messages || '';
}

function labelFor(control) {
    return control.labels?.[0] || control.closest('.ui-field')?.querySelector('label')
        || control.closest('.field-label')?.querySelector('[data-select-label]') || control.closest('label');
}

function markRequired(control) {
    const label = labelFor(control);
    if (!label) return;
    const required = control.required || control.getAttribute('aria-required') === 'true' || control.closest('[data-field-required="true"]');
    const marker = label.querySelector('[data-required-marker]');
    if (!required || control.disabled) {
        marker?.remove();
        return;
    }
    if (label.querySelector('.field-required')) return;
    const star = document.createElement('span');
    star.className = 'field-required';
    star.dataset.requiredMarker = '';
    star.setAttribute('aria-hidden', 'true');
    star.textContent = '*';
    const title = label.querySelector('[data-field-label], .font-semibold') || label;
    const field = title === label && !['checkbox', 'radio'].includes(control.type)
        ? Array.from(label.children || []).find(child => child === control || child.contains(control)) : null;
    if (field) label.insertBefore(star, field);
    else title.append(star);
}

function showError(control, message) {
    let error = document.getElementById(control.dataset.feedbackError || '');
    if (message) {
        control.setAttribute('aria-invalid', 'true');
        control.dataset.feedbackInvalid = '';
        if (control.dataset.errorDisplay === 'outline') return;
        if (!error) {
            const adjacent = (control.closest('label') || control).nextElementSibling;
            const inline = control.closest('.ui-field')?.querySelector('.field-error')
                || (adjacent?.matches('[role="alert"]') ? adjacent : null);
            if (inline) {
                if (!inline.id) inline.id = 'field-feedback-' + ++sequence;
                const ids = new Set((control.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
                ids.add(inline.id);
                control.setAttribute('aria-describedby', [...ids].join(' '));
                return;
            }
            const existingId = control.getAttribute('aria-describedby')?.split(/\s+/).find(id => document.getElementById(id)?.classList.contains('field-error'));
            if (existingId) return;
            error = document.createElement('span');
            error.id = 'field-feedback-' + ++sequence;
            error.className = 'field-error';
            error.setAttribute('role', 'alert');
            control.dataset.feedbackError = error.id;
            const anchor = control.closest('.ui-select') || control.closest('label') || control;
            anchor.after(error);
            control.setAttribute('aria-describedby', [control.getAttribute('aria-describedby'), error.id].filter(Boolean).join(' '));
        }
        if (error.textContent !== message) error.textContent = message;
    } else if (control.hasAttribute('data-feedback-invalid')) {
        control.removeAttribute('aria-invalid');
        delete control.dataset.feedbackInvalid;
        if (error) {
            const ids = (control.getAttribute('aria-describedby') || '').split(/\s+/).filter(id => id !== error.id).join(' ');
            if (ids) control.setAttribute('aria-describedby', ids);
            else control.removeAttribute('aria-describedby');
            error.remove();
        }
        delete control.dataset.feedbackError;
    }
}

function summary(scope, errors) {
    const outlineFields = new Set(Array.from(scope.querySelectorAll('[data-error-display="outline"]'))
        .filter(control => control.dataset.errorDisplay === 'outline').map(fieldName));
    const messages = [...new Set(Object.entries(errors).filter(([name]) => !outlineFields.has(name)).flatMap(([, messages]) => messages).filter(Boolean))];
    let alert = Array.from(scope.children).find(child => child.hasAttribute('data-validation-summary'));
    if (!messages.length) { alert?.remove(); return; }
    if (!alert && scope.querySelector('.ui-feedback-error, #login-error')) return;
    if (!alert) {
        alert = document.createElement('div');
        alert.className = 'ui-feedback ui-feedback-error';
        alert.dataset.validationSummary = '';
        alert.setAttribute('role', 'alert');
        scope.prepend(alert);
    }
    const text = messages.join(' ');
    if (alert.textContent !== text) alert.textContent = text;
}

export function syncFields(scope, errors = {}) {
    scope.querySelectorAll(controls).forEach(control => {
        if (scope.hasAttribute('wire:id') && control.closest('[wire\\:id]') !== scope) return;
        markRequired(control);
        showError(control, fieldMessage(errors, fieldName(control)));
    });
    const alertScope = scope.getAttribute?.('role') === 'dialog' ? scope.querySelector('form') || scope : scope;
    summary(alertScope, errors);
}

export default function initFieldFeedback() {
    const refresh = () => {
        document.querySelectorAll(controls).forEach(markRequired);
        document.querySelectorAll('[data-validation-errors]').forEach(source => {
            syncFields(source.closest('form') || source.parentElement, JSON.parse(source.dataset.validationErrors));
        });
        document.querySelectorAll('[wire\\:snapshot]').forEach(root => {
            const snapshot = JSON.parse(root.getAttribute('wire:snapshot'));
            syncFields(root, snapshot.memo.errors || {});
        });
    };
    refresh();
    let scheduled = false;
    new MutationObserver(records => {
        if (!records.some(record => record.type === 'attributes' || [...record.addedNodes].some(node => node.nodeType === 1 && (node.matches(controls) || node.querySelector(controls))))) return;
        if (scheduled) return;
        scheduled = true;
        requestAnimationFrame(() => { scheduled = false; document.querySelectorAll(controls).forEach(markRequired); });
    }).observe(document.body, { subtree: true, childList: true, attributes: true, attributeFilter: ['required', 'disabled', 'aria-required'] });
    const hookLivewire = () => window.Livewire.hook('morphed', ({ component }) => syncFields(component.el, component.snapshot.memo.errors || {}));
    if (window.Livewire) hookLivewire();
    else document.addEventListener('livewire:init', hookLivewire, { once: true });
    document.addEventListener('invalid', event => {
        const control = event.target;
        if (!control.matches(controls)) return;
        const selectTrigger = control.closest('.ui-select')?.querySelector('.ui-select-trigger');
        if (selectTrigger?.offsetParent) {
            control.setAttribute('aria-invalid', 'true');
            control.dataset.feedbackInvalid = '';
        } else showError(control, control.validationMessage);
        if (control.form) {
            const errors = {};
            control.form.querySelectorAll(controls).forEach(field => {
                if (!field.validity.valid) errors[fieldName(field) || field.id] = field.validationMessage;
            });
            clientErrors.set(control.form, errors);
            summary(control.form, errors);
        }
    }, true);
    document.addEventListener('input', event => {
        if (event.target.matches(controls) && event.target.hasAttribute('data-feedback-invalid')) {
            showError(event.target, event.target.validity.valid ? '' : event.target.validationMessage);
            const errors = clientErrors.get(event.target.form);
            if (errors) {
                const key = fieldName(event.target) || event.target.id;
                if (event.target.validity.valid) delete errors[key];
                else errors[key] = event.target.validationMessage;
                summary(event.target.form, errors);
            }
        }
    });
    document.addEventListener('reset', event => {
        if (event.target.tagName !== 'FORM') return;
        clientErrors.delete(event.target);
        queueMicrotask(() => syncFields(event.target));
    });
}
