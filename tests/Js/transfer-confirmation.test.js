import assert from 'node:assert/strict';
import test from 'node:test';
import transferConfirmation from '../../resources/js/transfer-confirmation.js';

function setup() {
    const state = transferConfirmation({ currentLocation: 'Synthetic street, Synthetic town', seniorName: 'Synthetic Senior', municipality: 'Local town', province: 'Local province', timezone: 'Asia/Manila' });
    const fields = Object.fromEntries(Object.entries({ type: 'INTERNAL', requested_by: 'SELF', destination_address: 'New street', barangay_id: '1', requester_name: 'Synthetic Requester', requester_relationship: 'Child', destination_barangay: 'Other barangay', destination_municipality: 'Other town', destination_province: 'Other province' }).map(([key, value]) => [key, { value }]));
    fields.barangay_id.selectedOptions = [{ textContent: 'Local barangay' }];
    let valid = true;
    let submissions = 0;
    const event = () => ({ prevented: false, preventDefault() { this.prevented = true; } });
    state.$refs = {
        transferDialog: { open: false, showModal() { this.open = true; }, close() { this.open = false; } },
        transferForm: {
            elements: { namedItem: key => fields[key] },
            reportValidity: () => valid,
            requestSubmit() {
                if (!valid) return;
                const submission = event();
                state.reviewTransfer(submission);
                if (!submission.prevented) submissions++;
            },
        },
    };
    return { state, fields, event, submissions: () => submissions, validity: value => { valid = value; } };
}

test('invalid transfer fields prevent review and saving', () => {
    const { state, event, validity, submissions } = setup();
    validity(false);
    const submission = event();
    state.reviewTransfer(submission);
    assert.equal(submission.prevented, true);
    assert.equal(state.$refs.transferDialog.open, false);
    assert.equal(state.review, null);
    assert.equal(submissions(), 0);
});

test('internal review shows the current form values and requires explicit confirmation', () => {
    const { state, event, submissions } = setup();
    state.reviewTransfer(event());
    assert.equal(state.$refs.transferDialog.open, true);
    assert.equal(state.review.external, false);
    assert.equal(state.review.type, 'Within the municipality');
    assert.equal(state.review.requester, 'Synthetic Senior (Senior citizen)');
    assert.equal(state.review.current, 'Synthetic street, Synthetic town');
    assert.equal(state.review.destination, 'New street, Local barangay, Local town, Local province');
    assert.deepEqual(state.review.destinationDetails, { address: 'New street', barangay: 'Local barangay', municipality: 'Local town', province: 'Local province' });
    assert.match(state.review.date, /^[A-Za-z]+ \d{1,2}, \d{4}$/);
    assert.equal(submissions(), 0);
});

test('cancel preserves entered values and the next review reflects changes including transfer type', () => {
    const { state, fields, event, submissions } = setup();
    state.reviewTransfer(event());
    state.cancelTransfer();
    assert.equal(state.$refs.transferDialog.open, false);
    assert.equal(fields.destination_address.value, 'New street');
    fields.destination_address.value = 'Updated street';
    fields.type.value = 'EXTERNAL';
    fields.requested_by.value = 'OTHER';
    state.reviewTransfer(event());
    assert.equal(state.review.external, true);
    assert.equal(state.review.type, 'Outside the municipality');
    assert.equal(state.review.requester, 'Synthetic Requester (Child)');
    assert.equal(state.review.destination, 'Updated street, Other barangay, Other town, Other province');
    assert.deepEqual(state.review.destinationDetails, { address: 'Updated street', barangay: 'Other barangay', municipality: 'Other town', province: 'Other province' });
    assert.equal(submissions(), 0);
});

test('confirm submits once and subsequent clicks or submissions are blocked', () => {
    const { state, event, submissions } = setup();
    state.confirmTransfer();
    assert.equal(submissions(), 0);
    state.reviewTransfer(event());
    state.confirmTransfer();
    state.confirmTransfer();
    const duplicate = event();
    state.reviewTransfer(duplicate);
    assert.equal(duplicate.prevented, true);
    assert.equal(submissions(), 1);
    assert.equal(state.submitting, true);
    assert.equal(state.$refs.transferDialog.open, false);
    state.resetSubmission();
    assert.equal(state.submitting, false);
    assert.equal(state.approved, false);
});

test('failed validation on confirmation cannot leave the form approved', () => {
    const { state, event, validity, submissions } = setup();
    state.reviewTransfer(event());
    validity(false);
    state.confirmTransfer();
    assert.equal(submissions(), 0);
    assert.equal(state.approved, false);
    assert.equal(state.submitting, false);
});
