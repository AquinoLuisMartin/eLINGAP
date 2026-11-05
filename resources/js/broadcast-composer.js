import templateState from './sms-template-state.js';

export const broadcastGuidance = '[OSCA BROADCAST] Paalala sa mga senior citizens: May payout/announcement sa (Insert Venue/Place) sa (Insert Date) ganap na (Insert Time). Mangyaring magdala ng inyong OSCA ID.';

export function templateCopy(body = '') {
    const text = body.trim();
    if (['(Insert Venue/Place)', '(Insert Date)', '(Insert Time)'].every(tag => text.includes(tag))) return text;
    return text ? `${text.slice(0, 1600 - broadcastGuidance.length - 2)}\n\n${broadcastGuidance}` : broadcastGuidance;
}

export default function broadcastComposer({ eligible = 0, excluded = 0, barangay = '', barangayIds = [], key = 'sms-templates', message = '' } = {}) {
    return {
        ...templateState(key),
        broadcastBody: message,
        barangay,
        program: 'all',
        selectedTemplate: '',
        previousTemplate: '',
        get localTemplates() { return Object.values(this.overrides).filter(template => template.id.startsWith('local-')); },
        templateTitle: '',
        templateBody: broadcastGuidance,
        templateError: '',

        get eligibleRecipients() {
            const shares = { all: 1, sss: 0.25, gsis: 0.15, lgu: 0.4, centenarian: 0.02 };
            const index = barangayIds.map(String).indexOf(String(this.barangay));
            const areaShare = this.barangay === barangay || !this.barangay ? 1 : (index + 1) / (barangayIds.length + 1);
            return Math.round(eligible * Math.max(0, areaShare) * (shares[this.program] ?? 1));
        },
        get excludedRecipients() { return eligible + excluded - this.eligibleRecipients; },
        get audiencePreview() { return this.program !== 'all' || this.barangay !== barangay; },

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
            const id = `local-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
            this.persistTemplate({ id, title, body, active: true });
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
