export function confirmAction({ title = 'Please confirm', message = '', details = [], confirmLabel = 'Confirm', danger = false } = {}, root = document) {
    const dialog = root.getElementById('confirm-dialog');
    if (!dialog || dialog.open) return Promise.resolve(false);

    const list = dialog.querySelector('[data-confirm-details]');
    const accept = dialog.querySelector('[data-confirm-accept]');
    dialog.querySelector('[data-confirm-title]').textContent = title;
    dialog.querySelector('[data-confirm-message]').textContent = message;
    list.replaceChildren(...details.flatMap(([term, value]) => {
        const dt = root.createElement('dt');
        const dd = root.createElement('dd');
        dt.textContent = term;
        dd.textContent = value;
        return [dt, dd];
    }));
    list.hidden = details.length === 0;
    accept.textContent = confirmLabel;
    accept.className = danger ? 'danger-button' : 'primary-button';
    dialog.returnValue = '';
    dialog.showModal();

    return new Promise(resolve => dialog.addEventListener('close', () => resolve(dialog.returnValue === 'confirm'), { once: true }));
}

export function optionsFrom(element) {
    const { confirm, confirmTitle, confirmLabel, confirmTone } = element.dataset;
    return { title: confirmTitle, message: confirm, confirmLabel, danger: confirmTone === 'danger' };
}

export function confirmSubmit(event, options) {
    const form = event.target;
    if (form.dataset.confirmed) {
        delete form.dataset.confirmed;
        return;
    }
    event.preventDefault();
    event.stopImmediatePropagation();
    confirmAction(options, form.ownerDocument).then(accepted => {
        if (!accepted) return;
        form.dataset.confirmed = '1';
        form.requestSubmit(event.submitter ?? undefined);
    });
}

export default function initConfirmDialogs(root = document) {
    root.addEventListener('submit', event => {
        if (event.target.matches?.('form[data-confirm]')) confirmSubmit(event, optionsFrom(event.target));
    }, true);
    root.addEventListener('click', event => {
        const button = event.target.closest?.('button[type="button"][data-confirm]');
        if (!button) return;
        if (button.dataset.confirmed) {
            delete button.dataset.confirmed;
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        confirmAction(optionsFrom(button), root).then(accepted => {
            if (!accepted || !button.isConnected) return;
            button.dataset.confirmed = '1';
            button.click();
        });
    }, true);
}
