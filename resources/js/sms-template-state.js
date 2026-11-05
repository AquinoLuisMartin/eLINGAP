export function validTemplate(template) {
    return template && typeof template.id === 'string' && typeof template.title === 'string'
        && template.title.trim().length > 0 && template.title.length <= 120
        && typeof template.body === 'string' && template.body.trim().length > 0
        && template.body.length <= 1600 && typeof template.active === 'boolean';
}

export function readTemplates(key) {
    try {
        const saved = JSON.parse(window.localStorage.getItem(key) || '{}');
        return Object.fromEntries(Object.entries(saved).filter(([id, template]) => validTemplate(template) && id === template.id));
    } catch {
        return {};
    }
}

export default function templateState(key) {
    return {
        seedTemplates: [],
        overrides: {},
        init() {
            this.seedTemplates = JSON.parse(this.$el.dataset.templates || '[]');
            this.overrides = readTemplates(key);
        },
        refreshTemplates(event) {
            if (event.key !== key && event.detail?.key !== key) return;
            this.overrides = event.detail?.templates ?? readTemplates(key);
        },
        availableTemplates() {
            const templates = new Map(this.seedTemplates.map(template => [template.id, template]));
            Object.values(this.overrides).forEach(template => templates.set(template.id, template));
            return [...templates.values()].filter(template => template.active);
        },
        persistTemplate(template) {
            if (!validTemplate(template)) return;
            this.overrides = { ...readTemplates(key), ...this.overrides, [template.id]: template };
            try { window.localStorage.setItem(key, JSON.stringify(this.overrides)); } catch {}
            if (typeof window !== 'undefined') {
                window.dispatchEvent(new CustomEvent('sms-templates-updated', { detail: { key, templates: this.overrides } }));
            }
        },
    };
}
