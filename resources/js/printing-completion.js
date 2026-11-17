import { confirmAction } from './confirm-dialog.js';

export function printResults(form) {
    return [...form.querySelectorAll('input[data-print-outcome]:checked')].map(input => ({
        attempt_id: Number(input.dataset.attemptId), result: input.value,
    }));
}

export function renderPrintResults(container, attempts) {
    const root = container.ownerDocument;
    container.replaceChildren(...attempts.map(attempt => {
        const group = root.createElement('fieldset');
        group.className = 'min-w-0 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800';
        group.dataset.requiredGroup = '';
        const legend = root.createElement('legend');
        legend.className = 'sr-only';
        legend.textContent = `Printing result for ${attempt.name}`;
        group.append(legend);
        const heading = root.createElement('div');
        heading.className = 'mb-3 flex min-w-0 flex-col gap-1';
        const name = root.createElement('span');
        name.className = 'text-sm font-semibold wrap-anywhere text-(--navy) dark:text-slate-100';
        name.textContent = attempt.name;
        const number = root.createElement('span');
        number.className = 'text-xs text-slate-500 dark:text-slate-400';
        number.textContent = attempt.osca_id ? `OSCA ID ${attempt.osca_id}` : 'OSCA ID pending';
        heading.append(name, number);
        group.append(heading);
        const options = root.createElement('div');
        options.className = 'grid gap-2 text-sm';
        for (const [value, title, description] of [
            ['PRINTED', 'Printed successfully', 'Both sides printed clearly and are ready for finishing.'],
            ['NOT_PRINTED', 'Not printed', 'No usable card was printed. Keep this ID pending for another attempt.'],
        ]) {
            const label = root.createElement('label');
            label.className = 'flex min-h-11 cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-white p-3 has-checked:border-blue-500 has-checked:bg-blue-50 dark:border-slate-600 dark:bg-slate-900 dark:has-checked:bg-blue-950';
            const input = root.createElement('input');
            input.className = 'mt-1 shrink-0';
            input.type = 'radio';
            input.name = `printing-result-${attempt.id}`;
            input.value = value;
            input.required = true;
            input.dataset.printOutcome = '';
            input.dataset.attemptId = String(attempt.id);
            const text = root.createElement('span');
            text.className = 'flex min-w-0 flex-col gap-1';
            const caption = root.createElement('span');
            caption.className = 'font-medium';
            caption.textContent = title;
            const help = root.createElement('span');
            help.className = 'text-xs leading-relaxed text-slate-500 dark:text-slate-400';
            help.textContent = description;
            text.append(caption, help);
            label.append(input, text);
            options.append(label);
        }
        group.append(options);
        return group;
    }));
}

export async function printingResponse(response) {
    if (response.ok) return response;
    const data = await response.json().catch(() => ({}));
    throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'The printing request could not be completed. Please try again.');
}

export default function initPrintingCompletion(root = document) {
    const dialog = root.getElementById('printing-completion');
    if (!dialog) return;
    const form = dialog.querySelector('[data-print-results]');
    const rows = dialog.querySelector('[data-print-result-rows]');
    const save = dialog.querySelector('[data-print-save]');
    const later = dialog.querySelector('[data-print-later]');
    const csrf = () => form.querySelector('input[name="_token"]').value;
    const popup = (title, message) => confirmAction({ title, message, confirmLabel: 'Close' }, root);
    let generating = false;
    let saving = false;
    let count = 0;

    async function pending(ids = null) {
        const response = await printingResponse(await fetch(dialog.dataset.pendingUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' }));
        const data = await response.json();
        const attempts = data.attempts.filter(attempt => !ids || ids.includes(attempt.id)).slice(0, 4);
        if (!attempts.length || dialog.open) return;
        renderPrintResults(rows, attempts);
        count = attempts.length;
        save.disabled = true;
        dialog.showModal();
    }

    const resume = () => pending().catch(error => popup('Printing Error', error.message));
    root.defaultView.addEventListener('id-print-completion', resume);
    form.addEventListener('change', () => { save.disabled = saving || printResults(form).length !== count; });
    dialog.addEventListener('cancel', event => { if (saving) event.preventDefault(); });
    later.addEventListener('click', () => { if (!saving) dialog.close(); });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (saving || printResults(form).length !== count) return;
        saving = true;
        save.disabled = true;
        later.disabled = true;
        try {
            const response = await printingResponse(await fetch(dialog.dataset.completeUrl, {
                method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ results: printResults(form) }),
            }));
            const data = await response.json();
            dialog.close();
            root.defaultView.Livewire?.dispatch('id-print-updated');
            await popup('Printing Results Saved', data.message);
            await pending();
        } catch (error) {
            await popup('Printing Error', error.message);
        } finally {
            saving = false;
            save.disabled = printResults(form).length !== count;
            later.disabled = false;
        }
    });

    root.addEventListener('submit', async event => {
        const printForm = event.target;
        if (!printForm.matches?.('form[data-id-print]') || event.defaultPrevented) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        if (generating) return;
        const viewer = root.defaultView.open('', '_blank');
        if (!viewer) {
            await popup('Printing Error', 'Allow pop-ups for this portal, then try printing again.');
            return;
        }
        viewer.opener = null;
        generating = true;
        const buttons = [...printForm.querySelectorAll('button[type="submit"]')].filter(button => !button.disabled);
        buttons.forEach(button => { button.disabled = true; });
        try {
            const response = await printingResponse(await fetch(printForm.action, {
                method: 'POST', body: new FormData(printForm), headers: { Accept: 'application/json' },
            }));
            if (!response.headers.get('Content-Type')?.includes('application/pdf')) throw new Error('The print-ready PDF was not returned. Please sign in again if your session expired.');
            const ids = JSON.parse(response.headers.get('X-ID-Print-Attempts'));
            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            viewer.location.href = url;
            root.defaultView.setTimeout(() => URL.revokeObjectURL(url), 300000);
            root.defaultView.Livewire?.dispatch('id-print-updated');
            await pending(ids);
        } catch (error) {
            viewer.close();
            await popup('Printing Error', error.message);
            await pending().catch(() => {});
        } finally {
            generating = false;
            buttons.forEach(button => { if (button.isConnected) button.disabled = false; });
        }
    }, true);
}
