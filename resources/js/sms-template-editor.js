import templateState from './sms-template-state.js';

export default function smsTemplateEditor({ key }) {
    return {
        ...templateState(key),
        draft: null,
        error: '',
        notice: '',
        get displayedTemplates() {
            const templates = new Map(this.seedTemplates.map(template => [template.id, this.overrides[template.id] ?? template]));
            Object.values(this.overrides).filter(template => template.id.startsWith('local-'))
                .forEach(template => templates.set(template.id, template));
            return [...templates.values()];
        },
        editTemplate(template) {
            this.draft = { ...template };
            this.error = '';
            this.$nextTick(() => this.$refs.editDialog.showModal());
        },
        saveTemplate() {
            const template = { ...this.draft, title: this.draft.title.trim(), body: this.draft.body.trim() };
            if (!template.title || template.title.length > 120 || !template.body || template.body.length > 1600) {
                this.error = 'Enter a title (up to 120 characters) and a message (up to 1600 characters).';
                return;
            }
            this.persistTemplate(template);
            this.notice = 'Template updated in this browser. Broadcast templates are synced.';
            this.$refs.editDialog.close();
        },
    };
}
