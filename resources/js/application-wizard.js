export default function initApplicationWizard(form) {
    if (!form) return;

    const steps = [...form.querySelectorAll('[data-wizard-step]')];
    const back = form.querySelector('#wizard-back');
    const next = form.querySelector('#wizard-next');
    const submit = form.querySelector('#wizard-submit');
    const progress = form.querySelector('#wizard-progress');
    const labels = ['Senior citizen', 'Program and date', 'Review and submit'];
    const controls = step => [...step.querySelectorAll('input, select, textarea')];
    let index = 0;

    const show = () => {
        steps.forEach((step, position) => { step.hidden = position !== index; });
        back.hidden = index === 0;
        next.hidden = index === steps.length - 1;
        submit.hidden = index !== steps.length - 1;
        progress.textContent = `Step ${index + 1} of ${steps.length} · ${labels[index]}`;
        if (index === steps.length - 1) {
            const senior = form.elements.senior_citizen_id.selectedOptions[0]?.textContent || '';
            const program = form.elements.program_id.selectedOptions[0]?.textContent || '';
            form.querySelector('#application-review').textContent = `${senior} · ${program} · ${form.elements.applied_on.value}`;
        }
    };

    const advance = () => {
        if (index < steps.length - 1 && controls(steps[index]).every(control => control.reportValidity())) {
            index++;
            show();
        }
    };

    // Validate visible steps before showing native validation messages.
    form.noValidate = true;
    next.addEventListener('click', advance);
    back.addEventListener('click', () => {
        index = Math.max(0, index - 1);
        show();
    });
    form.addEventListener('submit', event => {
        if (index < steps.length - 1) {
            event.preventDefault();
            advance();
            return;
        }

        for (const [position, step] of steps.entries()) {
            const invalid = controls(step).find(control => !control.checkValidity());
            if (invalid) {
                event.preventDefault();
                index = position;
                show();
                invalid.reportValidity();
                return;
            }
        }
    });
    show();
}
