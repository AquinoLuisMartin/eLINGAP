export function formatSeniorName(value) {
    return String(value ?? '').replace(/[^\p{L}\p{M} ]/gu, '');
}

function restrictSeniorName(control) {
    const value = control.value;
    const name = formatSeniorName(value);
    if (value === name) return false;

    const start = control.selectionStart;
    const end = control.selectionEnd;
    const direction = control.selectionDirection;
    control.value = name;
    if (start !== null && end !== null) {
        control.setSelectionRange(formatSeniorName(value.slice(0, start)).length, formatSeniorName(value.slice(0, end)).length, direction);
    }
    return true;
}

export function initSeniorNameInputs(root = document) {
    root.addEventListener('input', event => {
        if (!event.isComposing && event.target.matches?.('[data-senior-name]')) restrictSeniorName(event.target);
    }, true);

    root.addEventListener('compositionend', event => {
        if (event.target.matches?.('[data-senior-name]') && restrictSeniorName(event.target)) {
            event.target.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }, true);
}

export function formatSeniorMobile(value) {
    const original = String(value ?? '');
    let digits = original.replace(/[\s()-]/g, '');
    if (/^\+639[0-9]{9}$/.test(digits)) digits = digits.slice(3);
    else if (/^639[0-9]{9}$/.test(digits)) digits = digits.slice(2);
    else if (/^09[0-9]{9}$/.test(digits)) digits = digits.slice(1);

    if (!/^9[0-9]{0,9}$/.test(digits)) return original;

    return [digits.slice(0, 3), digits.slice(3, 6), digits.slice(6)]
        .filter(Boolean).join('-');
}
