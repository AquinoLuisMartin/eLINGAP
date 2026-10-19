import assert from 'node:assert/strict';
import test from 'node:test';
import initApplicationWizard from '../../resources/js/application-wizard.js';

function wizard() {
    const control = () => ({
        valid: true,
        reports: 0,
        checkValidity() { return this.valid; },
        reportValidity() { this.reports++; return this.valid; },
    });
    const senior = control();
    const program = control();
    const steps = [[senior], [program], []].map(fields => ({ querySelectorAll: () => fields }));
    const elements = new Map(['wizard-back', 'wizard-next', 'wizard-submit', 'wizard-progress', 'application-review']
        .map(id => [`#${id}`, new EventTarget()]));
    const form = new EventTarget();
    form.querySelectorAll = () => steps;
    form.querySelector = selector => elements.get(selector);
    form.elements = {
        senior_citizen_id: { selectedOptions: [{ textContent: 'Synthetic applicant' }] },
        program_id: { selectedOptions: [{ textContent: 'Synthetic assistance' }] },
        applied_on: { value: '2026-10-19' },
    };
    initApplicationWizard(form);
    const submit = () => form.dispatchEvent(new Event('submit', { cancelable: true }));
    const next = () => elements.get('#wizard-next').dispatchEvent(new Event('click'));

    return { form, senior, program, steps, elements, submit, next };
}

test('Enter advances through the wizard and only submits after review', () => {
    const page = wizard();
    assert.equal(page.form.noValidate, true);
    assert.equal(page.submit(), false);
    assert.equal(page.steps[1].hidden, false);
    assert.equal(page.submit(), false);
    assert.equal(page.steps[2].hidden, false);
    assert.equal(page.submit(), true);
    assert.match(page.elements.get('#application-review').textContent, /Synthetic assistance/);
});

test('an invalid visible step stays open instead of advancing', () => {
    const page = wizard();
    page.senior.valid = false;
    page.next();
    assert.equal(page.steps[0].hidden, false);
    assert.equal(page.senior.reports, 1);
});

test('final submission reopens an invalid earlier step before reporting its error', () => {
    const page = wizard();
    page.next();
    page.next();
    page.senior.valid = false;
    assert.equal(page.submit(), false);
    assert.equal(page.steps[0].hidden, false);
    assert.equal(page.steps[2].hidden, true);
    assert.equal(page.senior.reports, 2);
});
