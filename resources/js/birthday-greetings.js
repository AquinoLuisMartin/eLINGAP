import { confirmAction } from './confirm-dialog.js';
import { formatSeniorMobile } from './senior-registration-input.js';

const greeting = name => `Maligayang Kaarawan po, ${name}! Mula sa OSCA Santa Maria, binabati po namin kayo ng malusog at masayang kaarawan.`;

export default function birthdayGreetings(historyUrl) {
    return {
        search: '',
        barangay: '',
        draft: null,
        message: '',
        busy: false,
        notice: '',
        queued: {},

        init() {
            if (window.location.hash === '#birthday-greetings') this.$nextTick(() => this.openList());
        },

        get rows() {
            return Array.from(this.$refs.birthdayList?.querySelectorAll('[data-birthday-id]') ?? [], row => row.dataset);
        },

        get todayRecipients() {
            return this.rows.filter(row => row.days === '0' && row.date && row.mobile && row.sendUrl && !row.status && !this.queued[row.birthdayId]);
        },

        matches(row) {
            const identity = `${row?.name ?? ''} ${row?.oscaId ?? ''}`.toLocaleLowerCase();
            return (!this.barangay || this.barangay === row?.barangay)
                && this.search.trim().toLocaleLowerCase().split(/\s+/).every(term => identity.includes(term));
        },

        mobileLabel(number) {
            return number ? formatSeniorMobile(number) : 'No mobile on file';
        },

        openList() {
            this.draft = null;
            if (!this.$refs.birthdayDialog.open) this.$refs.birthdayDialog.showModal();
            this.$nextTick(() => this.$refs.birthdaySearch.focus());
        },

        openGreeting(id) {
            if (this.busy) return;
            const row = this.rows.find(row => row.birthdayId === id);
            if (!row) return;

            this.draft = { name: row.name, status: row.status || (this.queued[id] ? 'QUEUED' : '') };
            this.message = greeting(row.name || 'Senior citizen');
            this.$refs.greetingNumber.value = '';
            if (!this.$refs.birthdayDialog.open) this.$refs.birthdayDialog.showModal();
            this.$nextTick(() => (this.draft?.status ? this.$refs.greetingTitle : this.$refs.greetingNumber).focus());
        },

        clearHash() {
            if (window.location.hash === '#birthday-greetings') {
                window.history.replaceState(window.history.state, '', window.location.pathname + window.location.search);
            }
        },

        statusLabel(status) {
            return { SENT: 'Greeting Sent', QUEUED: 'Greeting Queued', FAILED: 'Greeting Failed' }[status] ?? '';
        },

        messageHistory() {
            const url = new URL(historyUrl, window.location.href);
            if (this.draft?.status) url.searchParams.set('status', this.draft.status);
            return url.href;
        },

        async queueGreeting(row) {
            return fetch(row.sendUrl, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: new URLSearchParams({
                    _token: this.$refs.manualForm.elements._token.value,
                    birthday_year: row.date.slice(0, 4),
                    recipient_number: row.mobile,
                    message: greeting(row.name || 'Senior citizen'),
                }),
            });
        },

        async sendToday() {
            const recipients = this.todayRecipients;
            if (this.busy || !recipients.length) return;
            this.busy = true;
            try {
                const confirmed = await confirmAction({
                    title: "Send today's birthday greetings?",
                    message: `${recipients.length} ${recipients.length === 1 ? 'greeting' : 'greetings'} will be queued for delivery.`,
                    confirmLabel: 'Send greetings',
                });
                if (!confirmed) return;

                const expected = new URL(historyUrl, window.location.href).href;
                let count = 0;
                let stopped = '';
                for (const [index, row] of recipients.entries()) {
                    this.notice = `Queuing greeting ${index + 1} of ${recipients.length}…`;
                    let response = await this.queueGreeting(row);
                    if (response.status === 429) {
                        const seconds = Math.min(60, Math.max(1, Number(response.headers.get('Retry-After')) || 60));
                        this.notice = `SMS send limit reached. Resuming in ${seconds} seconds…`;
                        await new Promise(resolve => setTimeout(resolve, seconds * 1000));
                        response = await this.queueGreeting(row);
                    }
                    if (response.ok && response.redirected && response.url === expected) {
                        this.queued[row.birthdayId] = true;
                        count++;
                    } else if ([401, 403, 419, 429].includes(response.status) || response.redirected) {
                        stopped = response.status === 429 ? 'The SMS send limit is still active.' : 'Reload the page before trying again.';
                        break;
                    }
                }
                this.notice = `${count} of ${recipients.length} greetings queued.${stopped ? ' ' + stopped : ''}`;
            } catch {
                this.notice = 'The connection was interrupted. Reload the page to check which greetings were queued.';
            } finally {
                this.busy = false;
            }
        },
    };
}
