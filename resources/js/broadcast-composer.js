import templateState from './sms-template-state.js';

export default function broadcastComposer({ eligible = 0, excluded = 0, barangay = '', program = '', audienceUrl = '', key = 'sms-templates', message = '' } = {}) {
    const templates = templateState(key);
    return {
        ...templates,
        init() {
            templates.init.call(this);
            this.$watch('program', () => {
                this.matchProgramTemplate();
                this.refreshAudience();
            });
            this.$watch('barangay', () => this.refreshAudience());
            if (this.program) this.matchProgramTemplate();
        },
        broadcastBody: message,
        barangay,
        program,
        appliedBarangay: barangay,
        appliedProgram: program,
        eligibleRecipients: eligible,
        excludedRecipients: excluded,
        audienceLoading: false,
        audienceError: '',
        audienceRequest: 0,
        selectedTemplate: '',

        programTemplates() {
            return this.availableTemplates().filter(template => {
                const seed = this.seedTemplates.find(saved => saved.id === template.id);
                return !this.program || String(template.programId ?? seed?.programId ?? '') === String(this.program);
            });
        },

        matchProgramTemplate() {
            const template = this.program ? this.programTemplates()[0] : null;
            this.selectedTemplate = template?.id ?? '';
            this.broadcastBody = template?.body ?? '';
            if (this.$refs.confirmed) this.$refs.confirmed.checked = false;
            this.syncMessage();
        },

        get audiencePreview() {
            return this.audienceLoading || !!this.audienceError || this.program !== this.appliedProgram || this.barangay !== this.appliedBarangay;
        },

        async refreshAudience() {
            if (this.$refs.confirmed) this.$refs.confirmed.checked = false;
            const request = ++this.audienceRequest;
            const selectedBarangay = this.barangay;
            const selectedProgram = this.program;
            this.audienceLoading = true;
            this.audienceError = '';
            try {
                const url = new URL(audienceUrl, window.location.origin);
                url.searchParams.set('barangay_id', selectedBarangay);
                url.searchParams.set('program_id', selectedProgram);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Audience request failed');
                const counts = await response.json();
                if (request !== this.audienceRequest) return;
                this.eligibleRecipients = counts.eligible;
                this.excludedRecipients = counts.excluded;
                this.appliedBarangay = selectedBarangay;
                this.appliedProgram = selectedProgram;
            } catch {
                if (request === this.audienceRequest) this.audienceError = 'Could not load recipients. Retry before sending.';
            } finally {
                if (request === this.audienceRequest) this.audienceLoading = false;
            }
        },

        chooseTemplate(id) {
            const template = this.programTemplates().find(template => template.id === id);
            if (!template) return;

            this.broadcastBody = template.body;
            this.syncMessage();
        },

        syncMessage() {
            this.$nextTick(() => this.$refs.message.dispatchEvent(new Event('input', { bubbles: true })));
        },
    };
}
