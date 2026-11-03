const textTypes = new Set(['text', 'search', 'email', 'tel', 'url', 'number']);

export function disableInputSuggestions(control) {
    if (control.tagName !== 'TEXTAREA' && (control.tagName !== 'INPUT' || !textTypes.has(control.type))) return;
    control.setAttribute('autocomplete', 'off');
}

export default function initInputSuggestions(root = document) {
    const sync = () => root.querySelectorAll('input, textarea').forEach(disableInputSuggestions);

    sync();
    root.addEventListener('focusin', event => disableInputSuggestions(event.target));
    root.addEventListener('livewire:navigated', sync);
}
