export function smsStatusClass(status) {
    return {
        Failed: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
        Sent: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
        Queued: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
    }[status] ?? 'bg-slate-100 text-slate-700';
}

export default function smsMessages({ action = 'Retry' } = {}) {
    return {
        selected: null,
        retry: null,
        rowStates: {},
        notice: '',
        statusClass: smsStatusClass,
        rowStatus(id, status) { return this.rowStates[id] ?? status; },
        openDetails(details) {
            this.selected = { ...details, status: this.rowStatus(details.id, details.status) };
            this.$nextTick(() => this.$refs.messageDialog.showModal());
        },
        openRetry(details) {
            if (this.rowStatus(details.id, details.status) !== 'Failed') return;
            this.retry = { ...details };
            this.$nextTick(() => this.$refs.retryDialog.showModal());
        },
        confirmRetry() {
            if (!this.retry || this.rowStatus(this.retry.id, this.retry.status) !== 'Failed') return;
            this.rowStates[this.retry.id] = 'Queued';
            this.notice = `${action} queued for ${this.retry.recipient} in this preview. No SMS was sent.`;
            this.$refs.retryDialog.close();
        },
    };
}
