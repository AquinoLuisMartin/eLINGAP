export const broadcastGuidance = '[OSCA BROADCAST] Paalala sa mga senior citizens: May payout/announcement sa (Insert Venue/Place) sa (Insert Date) ganap na (Insert Time). Mangyaring magdala ng inyong OSCA ID.';

export function templateCopy(body = '') {
    const text = body.trim();
    if (['(Insert Venue/Place)', '(Insert Date)', '(Insert Time)'].every(tag => text.includes(tag))) return text;
    return text ? `${text.slice(0, 1600 - broadcastGuidance.length - 2)}\n\n${broadcastGuidance}` : broadcastGuidance;
}

export default function broadcastComposer({ eligible = 0, message = '' } = {}) {
    return {
        broadcastBody: message,
        program: 'all',
        selectedTemplate: '',
        previousTemplate: '',
        localTemplates: [],
        templateTitle: '',
        templateBody: broadcastGuidance,
        templateError: '',

        get eligibleRecipients() {
            const shares = { all: 1, sss: 0.25, gsis: 0.15, lgu: 0.4, centenarian: 0.02 };
            return Math.round(eligible * (shares[this.program] ?? 1));
        },

        chooseTemplate(event) {
            if (event.target.value === '__new__') {
                this.selectedTemplate = this.previousTemplate;
                this.templateTitle = '';
                this.templateBody = broadcastGuidance;
                this.templateError = '';
                this.$nextTick(() => this.$refs.templateDialog.showModal());
                return;
            }
            this.previousTemplate = event.target.value;
            const option = event.target.selectedOptions[0];
            if (option?.dataset.body) {
                this.broadcastBody = event.target.value.startsWith('local-') ? option.dataset.body : templateCopy(option.dataset.body);
                this.syncMessage();
            }
        },

        saveTemplate() {
            const title = this.templateTitle.trim();
            const body = this.templateBody.trim();
            if (!title || !body || title.length > 120 || body.length > 1600) {
                this.templateError = 'Enter a title (up to 120 characters) and a message (up to 1600 characters).';
                return;
            }
            const id = `local-${this.localTemplates.length + 1}`;
            this.localTemplates.push({ id, title, body });
            this.selectedTemplate = id;
            this.previousTemplate = id;
            this.broadcastBody = body;
            this.syncMessage();
            this.$refs.templateDialog.close();
        },

        syncMessage() {
            this.$nextTick(() => this.$refs.message.dispatchEvent(new Event('input', { bubbles: true })));
        },
    };
}
