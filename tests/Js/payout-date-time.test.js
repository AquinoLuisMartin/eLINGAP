import assert from 'node:assert/strict';
import test from 'node:test';
import payoutDateTime from '../../resources/js/payout-date-time.js';

function setup(value) {
    const state = payoutDateTime(value);
    const watchers = {};
    state.$watch = (key, callback) => { watchers[key] = callback; };
    state.init();
    return { state, refresh(value) { state.value = value; watchers.value(value); } };
}

test('initial schedule retains its local date and exact minute without timezone conversion', () => {
    const { state } = setup('2026-11-15T23:35');

    assert.equal(state.date, '2026-11-15');
    assert.equal(state.time, '23:35');
});

test('date and time edits produce the existing schedule format including midnight', () => {
    const { state } = setup('2026-11-15T23:35');
    state.date = '2026-12-01';
    state.time = '00:00';
    state.update();

    assert.equal(state.value, '2026-12-01T00:00');
});

test('clearing either control clears submission while preserving the other control for completion', () => {
    for (const field of ['date', 'time']) {
        const { state, refresh } = setup('2026-11-15T23:35');
        state[field] = '';
        state.update();
        refresh(state.value);

        assert.equal(state.value, '');
        assert.equal(state[field === 'date' ? 'time' : 'date'], field === 'date' ? '23:35' : '2026-11-15');

        state[field] = field === 'date' ? '2026-11-16' : '09:00';
        state.update();
        assert.equal(state.value, field === 'date' ? '2026-11-16T23:35' : '2026-11-15T09:00');
    }
});

test('server updates and resets synchronize both visible controls', () => {
    const { state, refresh } = setup('2026-11-15T23:35');
    refresh('2026-12-10T08:15');

    assert.equal(state.date, '2026-12-10');
    assert.equal(state.time, '08:15');

    refresh('');
    assert.equal(state.date, '');
    assert.equal(state.time, '');
});

test('an empty initial schedule stays empty until both controls are completed', () => {
    const { state } = setup(null);
    state.date = '2026-11-15';
    state.update();

    assert.equal(state.value, '');
    state.time = '09:30';
    state.update();
    assert.equal(state.value, '2026-11-15T09:30');
});

for (const [hour, period, expected] of [['12', 'AM', '00:15'], ['12', 'PM', '12:15'], ['01', 'AM', '01:15'], ['11', 'PM', '23:15']]) {
    test(`${hour}:15 ${period} saves as ${expected}`, () => {
        const { state } = setup('2026-11-15T09:30');
        state.hour = hour;
        state.minute = '15';
        state.period = period;
        state.update();

        assert.equal(state.value, `2026-11-15T${expected}`);
    });
}

test('each time selection is required before a complete schedule is submitted', () => {
    for (const field of ['hour', 'minute', 'period']) {
        const { state } = setup('2026-11-15T23:44');
        state[field] = '';
        state.update();

        assert.equal(state.value, '');
    }
});

test('typed start and end times share the payout date and preserve noon conversion', () => {
    const { state } = setup('2026-11-20T09:00');
    state.endHour = '3';
    state.endMinute = '30';
    state.endPeriod = 'PM';
    state.update();

    assert.equal(state.value, '2026-11-20T09:00');
    assert.equal(state.endValue, '2026-11-20T15:30');

    state.date = '2026-11-21';
    state.update();
    assert.equal(state.value, '2026-11-21T09:00');
    assert.equal(state.endValue, '2026-11-21T15:30');
});

test('invalid typed hours or minutes cannot create a submission value', () => {
    for (const [hour, minute] of [['00', '30'], ['13', '30'], ['09', '60'], ['09', '5'], ['a', '00']]) {
        const { state } = setup('2026-11-20T09:00');
        state.endHour = hour;
        state.endMinute = minute;
        state.endPeriod = 'PM';
        state.update();

        assert.equal(state.endValue, '');
    }
});

test('saved end times and server resets update the typed end controls', () => {
    const state = payoutDateTime('2026-11-20T09:00', '2026-11-20T15:45');
    const watchers = {};
    state.$watch = (key, callback) => { watchers[key] = callback; };
    state.init();

    assert.equal(state.endHour, '03');
    assert.equal(state.endMinute, '45');
    assert.equal(state.endPeriod, 'PM');

    state.endValue = '';
    watchers.endValue('');
    assert.equal(state.endHour, '');
    assert.equal(state.endTime, '');
    assert.equal(state.date, '2026-11-20');
});
