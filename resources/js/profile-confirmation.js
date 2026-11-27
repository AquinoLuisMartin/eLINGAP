import { formatSeniorMobile } from './senior-registration-input.js';

const labels = {
    barangay_id: 'Barangay', first_name: 'First name', middle_name: 'Middle name', last_name: 'Last name',
    name_suffix: 'Name suffix', birth_date: 'Birth date', sex: 'Sex', civil_status: 'Civil status',
    contact_number: 'Mobile Number', address: 'House No. / Street / Purok / Sitio / Subdivision',
};
const uppercase = new Set(['first_name', 'middle_name', 'last_name', 'address']);

function normalize(field, value) {
    const text = String(value ?? '').trim();
    if (field === 'contact_number') return formatSeniorMobile(text).replace(/-/g, '');
    return uppercase.has(field) ? text.toUpperCase() : text;
}

export function profileChanges(form, original, barangays) {
    const display = (field, value) => {
        if (!value) return 'Not provided';
        if (field === 'barangay_id') return barangays[value] ?? value;
        if (field === 'contact_number') return '+63 ' + formatSeniorMobile(value);
        if (field === 'civil_status' || field === 'sex') return value[0] + value.slice(1).toLowerCase();
        return value;
    };
    return Object.entries(labels).flatMap(([field, label]) => {
        const control = form.elements.namedItem(field);
        if (!control) return [];
        const previous = normalize(field, original[field]);
        const updated = normalize(field, control.value);
        return previous === updated ? [] : [{ field, label, previous: display(field, previous), updated: display(field, updated) }];
    });
}

export default function profileConfirmation({ original, barangays }) {
    return {
        changes: [],
        approved: false,
        submitting: false,
        reviewProfile(event) {
            if (this.submitting) {
                event.preventDefault();
                return;
            }
            if (this.approved) {
                this.approved = false;
                this.submitting = true;
                this.$refs.profileDialog.close();
                return;
            }
            event.preventDefault();
            if (!this.$refs.profileForm.reportValidity() || this.$refs.profileDialog.open) return;
            this.changes = profileChanges(this.$refs.profileForm, original, barangays);
            this.$refs.profileDialog.showModal();
        },
        confirmProfile() {
            if (this.submitting || !this.changes.length || !this.$refs.profileDialog.open) return;
            const latest = profileChanges(this.$refs.profileForm, original, barangays);
            if (JSON.stringify(latest) !== JSON.stringify(this.changes)) {
                this.changes = latest;
                return;
            }
            this.approved = true;
            this.$refs.profileForm.requestSubmit();
            this.approved = false;
        },
        cancelProfile() {
            if (!this.submitting) this.$refs.profileDialog.close();
        },
        resetProfileSubmission() {
            this.approved = false;
            this.submitting = false;
        },
    };
}
