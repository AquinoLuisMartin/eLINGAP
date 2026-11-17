import assert from 'node:assert/strict';
import test from 'node:test';
import profileConfirmation, { profileChanges } from '../../resources/js/profile-confirmation.js';

function setup() {
    const original = { barangay_id: 1, first_name: 'SYNTHETIC', middle_name: null, last_name: 'SENIOR', name_suffix: null, birth_date: '1950-01-02', sex: 'FEMALE', civil_status: 'WIDOWED', contact_number: '+639170000001', address: 'ORIGINAL STREET' };
    const fields = Object.fromEntries(Object.entries(original).map(([key, value]) => [key, { value: String(value ?? '') }]));
    fields.contact_number.value = '917-000-0001';
    const barangays = { 1: 'Origin barangay', 2: 'Destination barangay' };
    const state = profileConfirmation({ original, barangays });
    let valid = true;
    let submissions = 0;
    const event = () => ({ prevented: false, preventDefault() { this.prevented = true; } });
    const form = {
        elements: { namedItem: name => fields[name] },
        reportValidity: () => valid,
        requestSubmit() {
            if (!valid) return;
            const submission = event();
            state.reviewProfile(submission);
            if (!submission.prevented) submissions++;
        },
    };
    state.$refs = { profileForm: form, profileDialog: { open: false, showModal() { this.open = true; }, close() { this.open = false; } } };
    return { state, form, fields, original, barangays, event, submissions: () => submissions, validity: value => { valid = value; } };
}

test('unchanged fields including formatted phone and blank optional values do not produce changes', () => {
    const { state, fields, event, submissions } = setup();
    fields.first_name.value = ' synthetic ';
    state.reviewProfile(event());
    assert.deepEqual(state.changes, []);
    assert.equal(state.$refs.profileDialog.open, true);
    state.confirmProfile();
    assert.equal(submissions(), 0);
});

test('summary contains only changed fields with original and normalized submitted values', () => {
    const { form, fields, original, barangays } = setup();
    fields.barangay_id.value = '2';
    fields.civil_status.value = 'MARRIED';
    fields.contact_number.value = '918-000-0002';
    fields.middle_name.value = 'new name';
    assert.deepEqual(profileChanges(form, original, barangays), [
        { field: 'barangay_id', label: 'Barangay', previous: 'Origin barangay', updated: 'Destination barangay' },
        { field: 'middle_name', label: 'Middle name', previous: 'Not provided', updated: 'NEW NAME' },
        { field: 'civil_status', label: 'Civil status', previous: 'Widowed', updated: 'Married' },
        { field: 'contact_number', label: 'Mobile Number', previous: '+63 917-000-0001', updated: '+63 918-000-0002' },
    ]);
});

test('invalid forms cannot open a review or submit', () => {
    const { state, event, validity, submissions } = setup();
    validity(false);
    const submission = event();
    state.reviewProfile(submission);
    assert.equal(submission.prevented, true);
    assert.equal(state.$refs.profileDialog.open, false);
    assert.equal(submissions(), 0);
});

test('cancel retains input and subsequent review compares against the original record again', () => {
    const { state, fields, event, submissions } = setup();
    fields.address.value = 'First edit';
    state.reviewProfile(event());
    state.cancelProfile();
    assert.equal(fields.address.value, 'First edit');
    assert.equal(state.$refs.profileDialog.open, false);
    fields.address.value = 'Second edit';
    state.reviewProfile(event());
    assert.deepEqual(state.changes, [{ field: 'address', label: 'House No. / Street / Purok / Sitio / Subdivision', previous: 'ORIGINAL STREET', updated: 'SECOND EDIT' }]);
    assert.equal(submissions(), 0);
});

test('confirmation submits once and locks further submissions until page restoration', () => {
    const { state, fields, event, submissions } = setup();
    fields.address.value = 'Changed street';
    state.confirmProfile();
    assert.equal(submissions(), 0);
    state.reviewProfile(event());
    state.confirmProfile();
    state.confirmProfile();
    const duplicate = event();
    state.reviewProfile(duplicate);
    assert.equal(submissions(), 1);
    assert.equal(duplicate.prevented, true);
    assert.equal(state.submitting, true);
    state.resetProfileSubmission();
    assert.equal(state.submitting, false);
});

test('changes made while reviewing require another explicit review before saving', () => {
    const { state, fields, event, submissions } = setup();
    fields.address.value = 'First edit';
    state.reviewProfile(event());
    fields.address.value = 'Later edit';
    state.confirmProfile();
    assert.equal(submissions(), 0);
    assert.equal(state.changes[0].updated, 'LATER EDIT');
    state.confirmProfile();
    assert.equal(submissions(), 1);
});

test('failed validation on final confirmation leaves the form unapproved', () => {
    const { state, fields, event, validity, submissions } = setup();
    fields.address.value = 'Changed street';
    state.reviewProfile(event());
    validity(false);
    state.confirmProfile();
    assert.equal(state.approved, false);
    assert.equal(state.submitting, false);
    assert.equal(submissions(), 0);
});
