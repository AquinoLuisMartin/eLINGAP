<?php

namespace App\Livewire\Benefits;

use App\Models\BenefitEntitlement;
use App\Models\BenefitSchedule;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Services\Benefits\BenefitAssignment;
use App\Services\Benefits\SeniorEligibilityRecord;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class SeniorRecord extends Component
{
    use WithPagination;

    public const CLASSIFICATION_LABELS = ['is_bedridden' => 'Bedridden', 'is_indigent' => 'Indigent', 'has_national_pension' => 'Receives national pension', 'has_local_pension' => 'Receives local pension'];

    #[Locked]
    public int $seniorId;

    public bool $documentsChecked = false;

    public array $classifications = ['is_bedridden' => false, 'is_indigent' => false, 'has_national_pension' => false, 'has_local_pension' => false];

    public string $notice = '';

    public string $programId = '';

    #[Locked]
    public ?array $assignmentReview = null;

    #[Locked]
    public ?array $classificationReview = null;

    public function reviewAssignment(): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        $this->assignmentReview = null;
        $data = $this->validate(['programId' => ['required', 'integer', Rule::exists('programs', 'id')->where('status', 'ACTIVE')]], ['programId.required' => 'Select one program before saving.']);
        $senior = SeniorCitizen::findOrFail($this->seniorId);
        abort_unless($senior->status->value === 'VERIFIED' && $senior->verified_at && $senior->id_generated_at && $senior->osca_id_number && ! $senior->died_on && ! $senior->death_declared_at, 422, 'Register, verify and generate the OSCA ID before assigning benefits.');
        $program = Program::findOrFail($data['programId']);
        $active = $senior->activeAssignments()->get(['id', 'program_id']);
        if ($active->isNotEmpty() && ! $active->contains('program_id', $program->id)) {
            throw ValidationException::withMessages(['programId' => 'Choose one of the existing active program assignments.']);
        }
        $this->assignmentReview = ['program_id' => $program->id, 'program_name' => $program->name, 'resolve' => $active->count() > 1];
        $this->notice = '';
    }

    public function cancelAssignmentReview(): void
    {
        $this->assignmentReview = null;
    }

    public function updatedProgramId(): void
    {
        $this->assignmentReview = null;
        $this->resetValidation('programId');
    }

    public function confirmAssignment(BenefitAssignment $assignments): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        abort_unless($this->assignmentReview !== null && (string) $this->assignmentReview['program_id'] === $this->programId, 422, 'Confirm the selected program before saving.');
        $senior = SeniorCitizen::findOrFail($this->seniorId);
        $program = Program::findOrFail($this->assignmentReview['program_id']);
        if ($this->assignmentReview['resolve']) {
            $assignments->resolve($senior, $program);
        } else {
            $assignments->assign($senior, $program);
        }
        $this->reset('programId', 'assignmentReview');
        $this->notice = 'Program assignment saved successfully. Payout and claim history retained.';
    }

    public function mount(int $seniorId): void
    {
        $this->seniorId = $seniorId;
        $senior = SeniorCitizen::findOrFail($seniorId);
        $this->classifications = $senior->only(array_keys($this->classifications));
    }

    public function boot(): void
    {
        Gate::authorize('viewAny', BenefitSchedule::class);
    }

    public function verify(SeniorEligibilityRecord $service): void
    {
        $this->validate(['documentsChecked' => ['accepted']]);
        $service->verify(SeniorCitizen::findOrFail($this->seniorId), $this->documentsChecked);
        $this->notice = 'Central registration verified.';
    }

    public function issueId(SeniorEligibilityRecord $service): void
    {
        $service->issueId(SeniorCitizen::findOrFail($this->seniorId));
        $this->notice = 'OSCA ID generated. The issuance date is recorded for eligibility.';
    }

    public function reviewClassifications(): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        $validated = $this->validate([
            'classifications.is_bedridden' => ['required', 'boolean'], 'classifications.is_indigent' => ['required', 'boolean'],
            'classifications.has_national_pension' => ['required', 'boolean'], 'classifications.has_local_pension' => ['required', 'boolean'],
        ]);
        $this->classificationReview = $validated['classifications'];
        $this->notice = '';
    }

    public function cancelClassificationReview(): void
    {
        $this->classificationReview = null;
    }

    public function updatedClassifications(): void
    {
        $this->classificationReview = null;
    }

    public function saveClassifications(SeniorEligibilityRecord $service): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        abort_unless($this->classificationReview !== null, 422, 'Review the selected classifications before saving.');
        $service->classify(SeniorCitizen::findOrFail($this->seniorId), $this->classificationReview);
        $this->classificationReview = null;
        $this->notice = 'Classifications saved successfully.';
    }

    public function render()
    {
        return view('livewire.benefits.senior-record', [
            'senior' => SeniorCitizen::with('activeAssignments.program')->findOrFail($this->seniorId),
            'classificationLabels' => self::CLASSIFICATION_LABELS,
            'programs' => Program::query()->where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name']),
            'entitlements' => BenefitEntitlement::query()->where('senior_citizen_id', $this->seniorId)->with(['program', 'payments' => fn ($query) => $query->latest('paid_at')->latest('id'), 'payments.recordedBy'])->latest('id')->paginate(10, ['*'], 'seniorBenefitsPage'),
        ]);
    }
}
