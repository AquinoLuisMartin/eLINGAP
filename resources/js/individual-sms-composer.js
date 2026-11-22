export default function individualSmsComposer({ url, program, selected, manual }) {
    let request;

    return {
        mode: manual ? 'manual' : 'program',
        program: String(program),
        search: '',
        selected,
        recipients: [],
        loading: false,
        sending: false,
        loaded: false,
        hasMore: false,
        error: '',

        init() {
            if (this.program) this.lookup();
        },

        changeProgram(value) {
            this.program = value;
            this.search = '';
            this.selected = null;
            this.lookup();
        },

        async lookup() {
            request?.abort();
            this.recipients = [];
            this.loaded = false;
            this.hasMore = false;
            this.error = '';
            this.loading = Boolean(this.program);
            if (!this.program) return;

            const current = new AbortController();
            request = current;
            const endpoint = new URL(url, window.location.href);
            endpoint.search = new URLSearchParams({ program_id: this.program, search: this.search.trim() });

            try {
                const response = await fetch(endpoint, { signal: current.signal, headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Recipient lookup failed');
                const data = await response.json();
                if (current.signal.aborted) return;
                this.recipients = data.recipients;
                this.hasMore = data.has_more;
                this.loaded = true;
            } catch {
                if (!current.signal.aborted) this.error = 'Could not load recipients. Try Search again.';
            } finally {
                if (request === current) this.loading = false;
            }
        },
    };
}
