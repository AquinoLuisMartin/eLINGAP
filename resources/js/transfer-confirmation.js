export default function transferConfirmation({ currentLocation, seniorName, municipality, province, timezone }) {
    return {
        review: null,
        approved: false,
        submitting: false,
        reviewTransfer(event) {
            if (this.submitting) {
                event.preventDefault();
                return;
            }
            if (this.approved) {
                this.approved = false;
                this.submitting = true;
                this.$refs.transferDialog.close();
                return;
            }
            event.preventDefault();
            const form = this.$refs.transferForm;
            if (!form.reportValidity() || this.$refs.transferDialog.open) return;
            const value = name => form.elements.namedItem(name)?.value.trim() ?? '';
            const external = value('type') === 'EXTERNAL';
            const barangay = external ? value('destination_barangay') : form.elements.namedItem('barangay_id').selectedOptions[0]?.textContent.trim();
            this.review = {
                external,
                type: external ? 'Outside the municipality' : 'Within the municipality',
                requester: value('requested_by') === 'SELF' ? `${seniorName} (Senior citizen)` : `${value('requester_name')} (${value('requester_relationship')})`,
                current: currentLocation,
                destinationDetails: {
                    address: value('destination_address'), barangay,
                    municipality: external ? value('destination_municipality') : municipality,
                    province: external ? value('destination_province') : province,
                },
                destination: [value('destination_address'), barangay, external ? value('destination_municipality') : municipality, external ? value('destination_province') : province].filter(Boolean).join(', '),
                date: new Intl.DateTimeFormat('en-US', { timeZone: timezone, year: 'numeric', month: 'long', day: 'numeric' }).format(new Date()),
            };
            this.$refs.transferDialog.showModal();
        },
        confirmTransfer() {
            if (this.submitting || !this.$refs.transferDialog.open) return;
            this.approved = true;
            this.$refs.transferForm.requestSubmit();
            this.approved = false;
        },
        cancelTransfer() {
            if (!this.submitting) this.$refs.transferDialog.close();
        },
        resetSubmission() {
            this.approved = false;
            this.submitting = false;
        },
    };
}
