import templateState from './sms-template-state.js';

export const broadcastGuidance = 'OSCA: Abiso para sa mga nakumpirmang benepisyaryo.\nPetsa: [Petsa]\nOras: [Oras]\nLugar: [Lugar/Venue]\nDalhin ang OSCA ID o valid ID. Kung pinapayagan ang kinatawan, magdala ng authorization letter at valid ID ng kinatawan. Dumating sa oras. Walang bayad; mag-ingat sa hindi awtorisadong tao.';

export function templateCopy(body = '') {
    const text = body.trim();
    if ([['[Petsa]', '[Oras]', '[Lugar/Venue]'], ['(Insert Venue/Place)', '(Insert Date)', '(Insert Time)']].some(tags => tags.every(tag => text.includes(tag)))) return text;
    return text ? `${text.slice(0, 1600 - broadcastGuidance.length - 2)}\n\n${broadcastGuidance}` : broadcastGuidance;
}

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
        previousTemplate: '',
        get localTemplates() { return Object.values(this.overrides).filter(template => template.id.startsWith('local-')); },
        templateTitle: '',
        templateBody: broadcastGuidance,
        templateError: '',

        programTemplates() {
            return this.availableTemplates().filter(template => {
                const seed = this.seedTemplates.find(saved => saved.id === template.id);
                return !this.program || String(template.programId ?? seed?.programId ?? '') === String(this.program);
            });
        },

        matchProgramTemplate() {
            const template = this.program ? this.programTemplates()[0] : null;
            this.selectedTemplate = template?.id ?? '';
            this.previousTemplate = this.selectedTemplate;
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
            this.persistTemplate({ id, title, body, active: true, programId: this.program || null });
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
