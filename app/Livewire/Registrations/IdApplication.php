<?php

namespace App\Livewire\Registrations;

use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Services\OfficeSettings;
use App\Services\SeniorCitizens\SeniorRegistration;
use App\Services\SeniorCitizens\SeniorRegistrationInput;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class IdApplication extends Component
{
    use WithFileUploads;

    private const PERSONAL_FIELDS = ['first_name', 'middle_name', 'last_name', 'name_suffix', 'birth_date', 'sex', 'civil_status'];

    private const REPRESENTATIVE_FIELDS = ['representative_name', 'representative_relationship', 'representative_contact'];

    #[Locked]
    public array $completedSteps = [];

    #[Locked]
    public int $step = 0;

    #[Locked]
    public bool $modal = false;

    #[Locked]
    public ?int $registeredSeniorId = null;

    #[Locked]
    public array $returnContext = ['from' => 'registry'];

    public array $form = ['requirements_checks' => ['identity' => false, 'residency' => false, 'authorization' => false], 'first_name' => '', 'middle_name' => '', 'last_name' => '', 'name_suffix' => '', 'birth_date' => '', 'sex' => '', 'civil_status' => '', 'barangay_id' => '', 'address' => '', 'contact_number' => '', 'representative_name' => '', 'representative_relationship' => '', 'representative_contact' => ''];

    public $authorizationFile;

    public $identificationFile;

    public bool $informationReviewed = false;

    #[Computed]
    public function age(): ?int
    {
        $birthDate = $this->form['birth_date'] ?? '';
        if (Validator::make(['birth_date' => $birthDate], ['birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today']])->fails()) {
            return null;
        }

        return (int) CarbonImmutable::parse($birthDate)->diffInYears(today());
    }

    public function updatedForm(mixed $value, ?string $key = null): void
    {
        $affected = match (true) {
            $key === null, str_starts_with($key, 'requirements_checks') => 0,
            in_array($key, self::PERSONAL_FIELDS, true) => 1,
            default => 2,
        };
        $this->completedSteps = array_filter($this->completedSteps, fn ($step) => $step < $affected, ARRAY_FILTER_USE_KEY);
        $this->informationReviewed = false;
        if (! $this->needsRepresentative()) {
            foreach (self::REPRESENTATIVE_FIELDS as $field) {
                $this->resetValidation('form.'.$field);
            }
            $this->resetValidation('authorizationFile');
        }
        if ($key === 'birth_date') {
            $this->resetValidation('form.birth_date');
            $this->validateInput(['birth_date']);
        }
    }

    public function mount(bool $modal = false): void
    {
        $this->reset('form', 'step', 'completedSteps', 'registeredSeniorId', 'authorizationFile', 'identificationFile', 'informationReviewed');
        $this->form['contact_number'] = '9';
        $this->form['representative_contact'] = '9';
        $this->resetValidation();
        unset($this->age);
        $this->modal = $modal;
        $this->returnContext = ['from' => request()->routeIs('registrations.index') ? 'registrations' : 'registry', ...request()->only(['search', 'search_by', 'stage', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page'])];
    }

    public function boot(): void
    {
        Gate::authorize('create', SeniorCitizen::class);
    }

    public function needsRepresentative(): bool
    {
        return filter_var($this->form['requirements_checks']['authorization'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function input(): array
    {
        $form = $this->form;
        if (! $this->needsRepresentative()) {
            $form = array_diff_key($form, array_flip(self::REPRESENTATIVE_FIELDS));
        }

        return SeniorRegistrationInput::normalize([...$form, 'applicant_type' => $this->needsRepresentative() ? 'REPRESENTATIVE' : 'SELF', 'requirements_status' => 'CHECKED', 'review_reason' => '', 'authorization_file' => $this->needsRepresentative() ? $this->authorizationFile : null, 'identification_file' => $this->identificationFile]);
    }

    private function validateInput(?array $fields = null): array
    {
        $input = $this->input();
        foreach (SeniorRegistrationInput::UPPERCASE_FIELDS as $field) {
            if (array_key_exists($field, $input)) {
                $this->form[$field] = $input[$field];
            }
        }
        try {
            return SeniorRegistrationInput::validator($input, $fields, requireAuthorizationFile: false)->validate();
        } catch (ValidationException $exception) {
            $errors = [];
            foreach ($exception->errors() as $field => $messages) {
                $property = match ($field) {
                    'authorization_file' => 'authorizationFile', 'identification_file' => 'identificationFile', default => 'form.'.$field,
                };
                $errors[$property] = $messages;
            }
            throw ValidationException::withMessages($errors);
        }
    }

    public function next(): void
    {
        Gate::authorize('create', SeniorCitizen::class);
        $fields = match ($this->step) {
            0 => ['requirements_checks', 'requirements_checks.identity', 'requirements_checks.residency', 'requirements_checks.authorization'],
            1 => self::PERSONAL_FIELDS,
            2 => ['barangay_id', 'address', 'contact_number', 'representative_name', 'representative_relationship', 'representative_contact'],
            default => [],
        };
        if ($this->step < 3) {
            $this->validateInput($fields);
            $this->resetValidation();
            $this->completedSteps[$this->step] = true;
            $this->step++;
        }
    }

    public function back(): void
    {
        $this->step = max(0, $this->step - 1);
        $this->resetValidation();
    }

    public function canVisitStep(int $target): bool
    {
        if ($target < 0 || $target > 3) {
            return false;
        }
        for ($step = 0; $step < $target; $step++) {
            if (empty($this->completedSteps[$step])) {
                return false;
            }
        }

        return true;
    }

    public function goToStep(int $target): void
    {
        Gate::authorize('create', SeniorCitizen::class);
        if ($this->canVisitStep($target)) {
            $this->step = $target;
            $this->informationReviewed = false;
            $this->resetValidation();
        }
    }

    public function cancel(): void
    {
        session()->flash('clear-registration-names', true);
        $this->dispatch('registration-cancelled');
        if (! $this->modal) {
            $this->redirectRoute('senior-citizens.index', collect($this->returnContext)->except('from')->all());
        }
    }

    public function submit(SeniorRegistration $registration, bool $prepareId = true): void
    {
        Gate::authorize('create', SeniorCitizen::class);
        abort_unless($this->step === 3, 422);
        if ($this->registeredSeniorId) {
            $this->finishSubmission($prepareId);

            return;
        }
        $this->validate(['informationReviewed' => ['accepted']]);
        try {
            $data = $this->validateInput();
        } catch (ValidationException $exception) {
            $keys = array_keys($exception->errors());
            $this->step = match (true) {
                collect($keys)->contains(fn ($key) => str_starts_with($key, 'form.requirements_')) => 0,
                collect($keys)->contains(fn ($key) => in_array($key, array_map(fn ($field) => 'form.'.$field, self::PERSONAL_FIELDS))) => 1,
                collect($keys)->contains(fn ($key) => str_starts_with($key, 'form.')) => 2,
                default => 3,
            };
            $this->completedSteps = array_filter($this->completedSteps, fn ($step) => $step < $this->step, ARRAY_FILTER_USE_KEY);
            throw $exception;
        }
        $senior = $registration->create($data, requireAuthorizationFile: false);
        $this->registeredSeniorId = $senior->id;
        $this->finishSubmission($prepareId);
    }

    private function finishSubmission(bool $prepareId): void
    {
        session()->flash('clear-registration-names', true);
        session()->flash('status', 'Registration saved successfully.');
        if ($prepareId) {
            $this->redirectRoute('senior-id.show', [$this->registeredSeniorId, ...$this->returnContext]);
        } else {
            $this->redirectRoute('registrations.index');
        }
    }

    public function render(OfficeSettings $settings)
    {
        return view('livewire.registrations.id-application', [
            'barangays' => Barangay::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'office' => $settings->values(),
        ]);
    }
}
