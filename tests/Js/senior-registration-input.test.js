import assert from 'node:assert/strict';
import test from 'node:test';
import { formatRegistrationMobile, formatSeniorMobile, formatSeniorName, initSeniorMobileInputs, initSeniorNameInputs } from '../../resources/js/senior-registration-input.js';

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

function mobileField(value, start = value.length, end = start) {
    const listeners = new Map();
    initSeniorMobileInputs({ addEventListener(name, handler, capture) {
        assert.equal(capture, true);
        listeners.set(name, handler);
    } });
    const field = {
        value, selectionStart: start, selectionEnd: end,
        matches: selector => selector === '[data-fixed-mobile]',
        setSelectionRange(start, end) { this.selectionStart = start; this.selectionEnd = end; },
        setRangeText(text, start, end, mode) {
            this.value = this.value.slice(0, start) + text + this.value.slice(end);
            this.setSelectionRange(mode === 'start' ? start : start + text.length, mode === 'start' ? start : start + text.length);
        },
        dispatchEvent(event) { listeners.get(event.type)?.({ target: this }); },
    };
    return { field, emit(name, data = {}) {
        let prevented = false;
        listeners.get(name)({ target: field, preventDefault() { prevented = true; }, ...data });
        return prevented;
    } };
}

test('fixed mobile prefix survives Backspace, Delete, select-all deletion and cutting', () => {
    for (const [value, start, end, inputType, expected] of [
        ['9', 1, 1, 'deleteContentBackward', '9'],
        ['99', 1, 1, 'deleteContentBackward', '99'],
        ['921-545-6665', 0, 0, 'deleteContentForward', '921-545-6665'],
        ['921-545-6665', 0, 1, 'deleteContentForward', '921-545-6665'],
        ['921-545-6665', 0, 12, 'deleteContentBackward', '9'],
        ['921-545-6665', 0, 12, 'deleteByCut', '9'],
    ]) {
        const control = mobileField(value, start, end);
        assert.equal(control.emit('beforeinput', { inputType }), true);
        assert.equal(control.field.value, expected);
    }
});

test('editing beside dashes deletes digits without getting stuck and keeps the caret', () => {
    const backward = mobileField('921-545-6665', 4);
    backward.emit('beforeinput', { inputType: 'deleteContentBackward' });
    assert.equal(backward.field.value, '925-456-665');
    assert.equal(backward.field.selectionStart, 2);

    const forward = mobileField('921-545-6665', 3);
    forward.emit('beforeinput', { inputType: 'deleteContentForward' });
    assert.equal(forward.field.value, '921-456-665');

    const typed = mobileField('921-7545-6665', 5);
    typed.emit('input');
    assert.equal(typed.field.value, '921-754-5666');
    assert.equal(typed.field.selectionStart, 5);
});

test('pasting complete numbers accepts country codes, strips non-digits and limits length', () => {
    for (const value of ['9215456665', '921-545-6665', '09215456665', '639215456665', '+63 921-545-6665', '921abc54566659999']) {
        const control = mobileField('9');
        assert.equal(control.emit('paste', { clipboardData: { getData: () => value } }), true);
        assert.equal(control.field.value, '921-545-6665');
        assert.equal(control.field.value.length, 12);
    }
    const partial = mobileField('921-545-6665', 4, 7);
    partial.emit('paste', { clipboardData: { getData: () => '123' } });
    assert.equal(partial.field.value, '921-123-6665');
});

test('fixed mobile input sanitizes edits and inserts after the protected prefix', () => {
    for (const value of ['', 'letters!', '123456789012345']) {
        const control = mobileField(value);
        control.emit('input');
        assert.match(control.field.value, /^9[\d-]*$/);
        assert.ok(control.field.value.length <= 12);
    }
    const control = mobileField('921-545-6665', 0);
    control.emit('beforeinput', { inputType: 'insertText', data: '1' });
    assert.equal(control.field.selectionStart, 1);
});
