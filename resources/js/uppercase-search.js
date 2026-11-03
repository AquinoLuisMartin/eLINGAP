export function uppercaseSearch(control) {
    const value = control.value;
    const uppercase = value.toUpperCase();
    if (value === uppercase) return false;

    const start = control.selectionStart;
    const end = control.selectionEnd;
    const direction = control.selectionDirection;
    control.value = uppercase;
    if (start !== null && end !== null) {
        control.setSelectionRange(value.slice(0, start).toUpperCase().length, value.slice(0, end).toUpperCase().length, direction);
    }
    return true;
}

export default function initUppercaseSearch(root = document) {
    root.addEventListener('input', event => {
        if (!event.isComposing && event.target.matches?.('[data-uppercase-search]')) uppercaseSearch(event.target);
    }, true);

    root.addEventListener('compositionend', event => {
        if (event.target.matches?.('[data-uppercase-search]') && uppercaseSearch(event.target)) {
            event.target.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }, true);
}
