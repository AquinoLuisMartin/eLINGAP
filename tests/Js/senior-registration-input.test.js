import assert from 'node:assert/strict';
import test from 'node:test';
import { formatSeniorMobile } from '../../resources/js/senior-registration-input.js';

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
