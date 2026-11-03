export function clearCredentials(root) {
    root.querySelectorAll('[data-credential-value]').forEach(output => { output.textContent = ''; });
    root.querySelectorAll('[data-credential-feedback]').forEach(output => { output.textContent = 'Credential hidden. It cannot be retrieved again.'; });
}

export default function initAccountSecurity(document, window) {
    const clear = () => clearCredentials(document);
    document.querySelectorAll('[data-dismiss-credential]').forEach(button => button.addEventListener('click', clear));
    window.addEventListener('pagehide', clear);
    window.addEventListener('pageshow', event => { if (event.persisted) clear(); });
}
