export function formatSeniorMobile(value) {
    const original = String(value ?? '');
    let digits = original.replace(/[\s()-]/g, '');
    if (/^\+639[0-9]{9}$/.test(digits)) digits = digits.slice(3);
    else if (/^639[0-9]{9}$/.test(digits)) digits = digits.slice(2);
    else if (/^09[0-9]{9}$/.test(digits)) digits = digits.slice(1);

    if (!/^9[0-9]{0,9}$/.test(digits)) return original;

    return [digits.slice(0, 3), digits.slice(3, 6), digits.slice(6)]
        .filter(Boolean).join('-');
}
