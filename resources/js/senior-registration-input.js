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

export function formatRegistrationMobile(value) {
    let digits = String(value ?? '').replace(/\D/g, '');
    if (digits.startsWith('639') && digits.length >= 12) digits = digits.slice(2);
    else if (digits.startsWith('09') && digits.length >= 11) digits = digits.slice(1);
    if (!digits.startsWith('9')) digits = '9' + digits;
    digits = digits.slice(0, 10);

    return formatSeniorMobile(digits);
}

function maskMobile(control) {
    const original = control.value;
    const formatted = formatRegistrationMobile(original);
    if (original === formatted) return;

    const digits = original.replace(/\D/g, '');
    let before = original.slice(0, control.selectionStart ?? original.length).replace(/\D/g, '').length;
    if (digits.startsWith('639') && digits.length >= 12) before -= 2;
    else if (digits.startsWith('09') && digits.length >= 11) before--;
    else if (!digits.startsWith('9')) before++;
    before = Math.max(1, Math.min(10, before));
    let caret = 0;
    for (let count = 0; caret < formatted.length && count < before; caret++) {
        if (/\d/.test(formatted[caret])) count++;
    }
    control.value = formatted;
    control.setSelectionRange(caret, caret);
}

export function initSeniorMobileInputs(root = document) {
    const fixed = event => event.target.matches?.('[data-fixed-mobile]');
    const update = control => control.dispatchEvent(new Event('input', { bubbles: true }));

    root.addEventListener('beforeinput', event => {
        if (!fixed(event)) return;
        const control = event.target;
        let start = control.selectionStart ?? 1;
        let end = control.selectionEnd ?? start;
        if (!event.inputType?.startsWith('delete')) {
            if (start === 0 && end === 0) control.setSelectionRange(1, 1);
            return;
        }
        if (event.cancelable === false) return;
        event.preventDefault();
        if (start === end) {
            if (event.inputType.endsWith('Backward')) {
                while (start > 1 && !/\d/.test(control.value[start - 1])) start--;
                if (start <= 1) return;
                start--;
            } else {
                if (start === 0) return;
                while (end < control.value.length && !/\d/.test(control.value[end])) end++;
                if (end === control.value.length) return;
                end++;
            }
        }
        control.setRangeText('', Math.max(1, start), Math.max(1, end), 'start');
        maskMobile(control);
        update(control);
    }, true);

    root.addEventListener('paste', event => {
        if (!fixed(event)) return;
        event.preventDefault();
        const control = event.target;
        const text = event.clipboardData.getData('text');
        const digits = text.replace(/\D/g, '');
        if (digits.length >= 10) {
            control.value = formatRegistrationMobile(text);
            control.setSelectionRange(control.value.length, control.value.length);
        } else {
            control.setRangeText(text, control.selectionStart, control.selectionEnd, 'end');
            maskMobile(control);
        }
        update(control);
    }, true);

    root.addEventListener('input', event => {
        if (fixed(event)) maskMobile(event.target);
    }, true);
}
