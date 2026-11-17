function clockTime(hour, minute, period) {
    if (!/^(0?[1-9]|1[0-2])$/.test(hour) || !/^[0-5]\d$/.test(minute) || !['AM', 'PM'].includes(period)) return '';
    return `${String(Number(hour) % 12 + (period === 'PM' ? 12 : 0)).padStart(2, '0')}:${minute}`;
}

export default function payoutDateTime(value, endValue = '') {
    return {
        value,
        endValue,
        date: '',
        hour: '',
        minute: '',
        period: '',
        endHour: '',
        endMinute: '00',
        endPeriod: 'AM',
        get time() {
            return clockTime(this.hour, this.minute, this.period);
        },
        get endTime() {
            return clockTime(this.endHour, this.endMinute, this.endPeriod);
        },
        set time(value) {
            const match = /^([01]\d|2[0-3]):([0-5]\d)$/.exec(value || '');
            this.hour = match ? String(Number(match[1]) % 12 || 12).padStart(2, '0') : '';
            this.minute = match ? match[2] : '';
            this.period = match ? (Number(match[1]) >= 12 ? 'PM' : 'AM') : '';
        },
        init() {
            this.read(this.value);
            this.readEnd(this.endValue);
            this.$watch('value', value => {
                if (value !== this.combined()) this.read(value);
            });
            this.$watch('endValue', value => {
                if (value !== this.combinedEnd()) this.readEnd(value);
            });
        },
        read(value) {
            const [date = '', time = ''] = (value || '').split('T');
            this.date = date;
            this.time = time.slice(0, 5);
        },
        combined() {
            return this.date && this.time ? `${this.date}T${this.time}` : '';
        },
        readEnd(value) {
            const match = /T([01]\d|2[0-3]):([0-5]\d)/.exec(value || '');
            this.endHour = match ? String(Number(match[1]) % 12 || 12).padStart(2, '0') : '';
            this.endMinute = match ? match[2] : '00';
            this.endPeriod = match && Number(match[1]) >= 12 ? 'PM' : 'AM';
        },
        combinedEnd() {
            return this.date && this.endTime ? `${this.date}T${this.endTime}` : '';
        },
        update() {
            this.value = this.combined();
            this.endValue = this.combinedEnd();
        },
    };
}
