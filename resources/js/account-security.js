export function clearCredentials(root) {
    root.querySelectorAll('[data-credential-value]').forEach(output => { output.textContent = ''; });
    root.querySelectorAll('[data-credential-feedback]').forEach(output => { output.textContent = 'Credential hidden. It cannot be retrieved again.'; });
}

export function usernameSuggestions({ fetcher, apply, report, token, endpoint }) {
    let sequence = 0;
    let manual = false;
    return {
        edited() { manual = true; sequence++; },
        async suggest(first, last, force = false) {
            if (force) manual = false;
            if (manual || !first.trim() || !last.trim()) return;
            const current = ++sequence;
            try {
                const response = await fetcher(endpoint, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ first_name: first, last_name: last }),
                });
                if (!response.ok) throw new Error('suggestion');
                const result = await response.json();
                if (current === sequence && !manual) { apply(result.username); report('Username suggestion updated.'); }
            } catch {
                if (current === sequence) report('Suggestion unavailable. Enter a username or leave it blank to generate one when creating the account.');
            }
        },
    };
}

export default function initAccountSecurity(document, window) {
    const clear = () => clearCredentials(document);
    document.querySelectorAll('[data-dismiss-credential]').forEach(button => button.addEventListener('click', clear));
    window.addEventListener('pagehide', clear);
    window.addEventListener('pageshow', event => { if (event.persisted) clear(); });
    document.querySelectorAll('[data-username-suggestion]').forEach(root => {
        const first = root.querySelector('[name="first_name"]');
        const last = root.querySelector('[name="last_name"]');
        const username = root.querySelector('[name="username"]');
        if (!first || !last || !username) return;
        const feedback = document.createElement('p');
        feedback.className = 'text-sm text-slate-500';
        feedback.setAttribute('role', 'status');
        feedback.setAttribute('aria-live', 'polite');
        username.after(feedback);
        const suggestions = usernameSuggestions({ fetcher: window.fetch.bind(window), endpoint: root.dataset.usernameSuggestion,
            token: root.closest('form').querySelector('[name="_token"]').value,
            apply: value => { username.value = value; }, report: value => { feedback.textContent = value; } });
        if (username.value) suggestions.edited();
        username.addEventListener('input', () => suggestions.edited());
        let timer;
        const suggest = force => suggestions.suggest(first.value, last.value, force);
        [first, last].forEach(input => input.addEventListener('input', () => { window.clearTimeout(timer); timer = window.setTimeout(() => suggest(false), 300); }));
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'text-button mt-2';
        button.textContent = 'Suggest username';
        button.addEventListener('click', () => suggest(true));
        feedback.after(button);
    });
}
