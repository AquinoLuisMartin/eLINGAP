<?php

namespace App\Livewire\Benefits;

use App\Models\BenefitEntitlement;
use App\Models\BenefitSchedule;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Services\Benefits\BenefitAssignment;
use App\Services\Benefits\SeniorEligibilityRecord;
use Illuminate\Support\Facades\Gate;
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
    public ?array $classificationReview = null;

    public function assignBenefit(BenefitAssignment $assignments): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        $data = $this->validate(['programId' => ['required', 'integer', 'exists:programs,id']]);
        $assignments->assign(SeniorCitizen::findOrFail($this->seniorId), Program::findOrFail($data['programId']));
        $this->reset('programId');
        $this->notice = 'Program assigned by OSCA Staff.';
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
            'entitlements' => BenefitEntitlement::query()->where('senior_citizen_id', $this->seniorId)->with(['program', 'payments'])->latest('id')->paginate(10, ['*'], 'seniorBenefitsPage'),
        ]);
    }
}
