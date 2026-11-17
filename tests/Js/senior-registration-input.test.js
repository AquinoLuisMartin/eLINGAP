import assert from 'node:assert/strict';
import test from 'node:test';
import { formatRegistrationMobile, formatSeniorMobile, formatSeniorName, initSeniorNameInputs } from '../../resources/js/senior-registration-input.js';

test('registration mobile input retains a fixed nine and formats at most ten digits', () => {
    for (const [input, expected] of [
        ['', '9'], [null, '9'], ['letters!@', '9'], ['123', '912-3'],
        ['917abc000!0001', '917-000-0001'], ['+63 917 000 0001', '917-000-0001'],
        ['09170000001', '917-000-0001'], ['917000000199999', '917-000-0001'],
        ['9', '9'], ['91', '91'], ['9170', '917-0'],
    ]) {
        assert.equal(formatRegistrationMobile(input), expected);
        assert.equal(formatRegistrationMobile(expected), expected);
    }
});

test('name inputs retain Unicode letters and spaces while rejecting digits and special characters', () => {
    for (const name of ['SYNTHETIC', 'DE LA TEST', 'ÑAME ÉXAMPLE', 'N\u0303AME', 'JR', 'III']) {
        assert.equal(formatSeniorName(name), name);
    }
    assert.equal(formatSeniorName("Test123-Name!@.'🙂\t\n"), 'TestName');
    assert.equal(formatSeniorName(null), '');
});

test('typing and pasted names are restricted before Livewire reads them while retaining the caret', () => {
    const listeners = new Map();
    initSeniorNameInputs({ addEventListener(name, handler, capture) {
        assert.equal(capture, true);
        listeners.set(name, handler);
    } });
    const field = {
        value: 'AB1! CD2', selectionStart: 4, selectionEnd: 4, selectionDirection: 'none',
        matches: selector => selector === '[data-senior-name]',
        setSelectionRange(start, end) { this.selectionStart = start; this.selectionEnd = end; },
    };
    listeners.get('input')({ target: field });
    assert.equal(field.value, 'AB CD');
    assert.equal(field.selectionStart, 2);
    assert.equal(field.selectionEnd, 2);

    field.value = 'Ñ1';
    listeners.get('input')({ target: field, isComposing: true });
    assert.equal(field.value, 'Ñ1');
    let updates = 0;
    field.dispatchEvent = event => { assert.equal(event.type, 'input'); updates++; };
    listeners.get('compositionend')({ target: field });
    assert.equal(field.value, 'Ñ');
    assert.equal(updates, 1);

    field.value = 'Address 123!';
    field.matches = () => false;
    listeners.get('input')({ target: field });
    assert.equal(field.value, 'Address 123!');
});

test('formats local typing and pasted Philippine mobile numbers without repeating +63', () => {
    for (const value of ['9672348976', '967-234-8976', '0967 234 8976', '+63 967-234-8976', '639672348976']) {
        assert.equal(formatSeniorMobile(value), '967-234-8976');
    }
    assert.equal(formatSeniorMobile(''), '');
    assert.equal(formatSeniorMobile(null), '');
    assert.equal(formatSeniorMobile('967'), '967');
    assert.equal(formatSeniorMobile('9672'), '967-2');
    assert.equal(formatSeniorMobile('9672348'), '967-234-8');
    assert.equal(formatSeniorMobile('967-'), '967');
});

test('preserves malformed and overlong input for validation rather than inventing a valid number', () => {
    for (const value of ['8672348976', '967abc2348976', '+1 9672348976', '+63+639672348976', '96723489760']) {
        assert.equal(formatSeniorMobile(value), value);
    }
});
