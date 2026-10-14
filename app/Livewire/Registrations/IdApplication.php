<?php

namespace App\Livewire\Registrations;

use App\Models\Barangay;
use App\Models\SeniorCitizen;
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

    #[Locked]
    public int $step = 0;

    #[Locked]
    public bool $modal = false;

    #[Locked]
    public ?int $registeredSeniorId = null;

    #[Locked]
    public array $returnContext = ['from' => 'registrations'];

    public array $form = ['requirements_status' => '', 'requirements_checks' => ['identity' => false, 'residency' => false, 'authorization' => false], 'review_reason' => '', 'first_name' => '', 'middle_name' => '', 'last_name' => '', 'name_suffix' => '', 'birth_date' => '', 'sex' => '', 'civil_status' => '', 'barangay_id' => '', 'address' => '', 'contact_number' => '', 'applicant_type' => 'SELF', 'representative_name' => '', 'representative_relationship' => '', 'representative_contact' => ''];

    public $photo;

    public $authorizationFile;

    public $identificationFile;

    public bool $informationReviewed = false;

    public bool $representativeContact = false;

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
        if ($key === 'birth_date') {
            $this->resetValidation('form.birth_date');
            $this->validateInput(['birth_date']);
        }
    }

    public function updatedPhoto(): void
    {
        $this->validateInput(['photo']);
    }

    public function updatedRepresentativeContact(bool $enabled): void
    {
        if (! $enabled && $this->form['applicant_type'] === 'SELF') {
            foreach (['representative_name', 'representative_relationship', 'representative_contact'] as $field) {
                $this->form[$field] = '';
            }
            $this->authorizationFile = null;
            $this->resetValidation();
        }
    }

    public function mount(bool $modal = false): void
    {
        $this->modal = $modal;
        $this->returnContext = ['from' => 'registrations', ...request()->only(['search', 'stage', 'barangay_id', 'page'])];
    }

    public function boot(): void
    {
        Gate::authorize('create', SeniorCitizen::class);
    }

    public function needsRepresentative(): bool
    {
        return $this->form['applicant_type'] === 'REPRESENTATIVE' || filled($this->form['representative_contact']) || filled($this->form['representative_name']);
    }

    private function input(): array
    {
        return SeniorRegistrationInput::normalize([...$this->form, 'photo' => $this->photo, 'authorization_file' => $this->authorizationFile, 'identification_file' => $this->identificationFile]);
    }

    private function validateInput(?array $fields = null): array
    {
        try {
            return SeniorRegistrationInput::validator($this->input(), $fields)->validate();
        } catch (ValidationException $exception) {
            $errors = [];
            foreach ($exception->errors() as $field => $messages) {
                $property = match ($field) {
                    'photo' => 'photo', 'authorization_file' => 'authorizationFile', 'identification_file' => 'identificationFile', default => 'form.'.$field,
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
            0 => ['requirements_status', 'requirements_checks', 'requirements_checks.identity', 'requirements_checks.residency', 'requirements_checks.authorization', 'review_reason'],
            1 => self::PERSONAL_FIELDS,
            2 => ['barangay_id', 'address', 'contact_number', 'applicant_type', 'representative_name', 'representative_relationship', 'representative_contact'],
            default => [],
        };
        if ($this->step < 3) {
            $this->validateInput($fields);
            $this->resetValidation();
            $this->step++;
        }
    }

    public function back(): void
    {
        $this->step = max(0, $this->step - 1);
        $this->resetValidation();
    }

    public function cancel(): void
    {
        session()->flash('clear-registration-names', true);
        $this->dispatch('registration-cancelled');
        if (! $this->modal) {
            $this->redirectRoute('registrations.index');
        }
    }

    public function submit(SeniorRegistration $registration): void
    {
        Gate::authorize('create', SeniorCitizen::class);
        abort_unless($this->step === 3, 422);
        if ($this->registeredSeniorId) {
            $this->redirectRoute('senior-id.show', [$this->registeredSeniorId, ...$this->returnContext]);

            return;
        }
        $this->validate(['informationReviewed' => ['accepted']]);
        try {
            $data = $this->validateInput();
        } catch (ValidationException $exception) {
            $keys = array_keys($exception->errors());
            $this->step = match (true) {
                collect($keys)->contains(fn ($key) => str_starts_with($key, 'form.requirements_') || $key === 'form.review_reason') => 0,
                collect($keys)->contains(fn ($key) => in_array($key, array_map(fn ($field) => 'form.'.$field, self::PERSONAL_FIELDS))) => 1,
                collect($keys)->contains(fn ($key) => str_starts_with($key, 'form.')) => 2,
                default => 3,
            };
            throw $exception;
        }
        $senior = $registration->create($data);
        $this->registeredSeniorId = $senior->id;
        session()->flash('clear-registration-names', true);
        session()->flash('status', $senior->verified_at ? 'Requirements checked and registration verified. Prepare the ID.' : 'Registration saved for document review.');
        $this->redirectRoute('senior-id.show', [$senior, ...$this->returnContext]);
    }

    public function render()
    {
        return view('livewire.registrations.id-application', ['barangays' => Barangay::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])]);
    }
}
