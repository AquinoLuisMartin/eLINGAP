<?php

namespace App\Services\Benefits;

use App\Models\Beneficiary;
use App\Models\BenefitSchedule;
use App\Models\Program;
use App\Models\SeniorCitizen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class BenefitAssignment
{
    public function resolve(SeniorCitizen $senior, Program $program): Beneficiary
    {
        Gate::authorize('manage', BenefitSchedule::class);

        return DB::transaction(function () use ($senior, $program) {
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($senior->id);
            $assignments = $senior->activeAssignments()->lockForUpdate()->get();
            if (! $assignments->contains('program_id', $program->id)) {
                throw ValidationException::withMessages(['programId' => 'Choose one of the existing active program assignments.']);
            }
            foreach ($assignments->where('program_id', '!=', $program->id) as $assignment) {
                $assignment->update(['status' => 'INACTIVE']);
                BenefitAudit::record('beneficiary.deactivated', $assignment, ['program_id' => $assignment->program_id, 'selected_program_id' => $program->id]);
            }
            $assignment = $this->assign($senior, $program);
            $senior->histories()->create(['changed_by' => auth()->id(), 'action' => 'benefit_assignment_resolved', 'changes' => ['program_id' => $program->id, 'inactive_program_ids' => $assignments->where('program_id', '!=', $program->id)->pluck('program_id')->all()]]);

            return $assignment;
        });
    }

    public function assign(SeniorCitizen $senior, Program $program, array $data = []): Beneficiary
    {
        Gate::authorize('manage', BenefitSchedule::class);

        return DB::transaction(function () use ($senior, $program, $data) {
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($senior->id);
            abort_unless($senior->status->value === 'VERIFIED' && $senior->verified_at && $senior->id_generated_at && $senior->osca_id_number && ! $senior->died_on && ! $senior->death_declared_at, 422, 'Register, verify and generate the OSCA ID before assigning benefits.');
            $program = Program::query()->findOrFail($program->id);
            abort_unless($program->status === 'ACTIVE', 422, 'Only active programs may be assigned.');
            if ($senior->activeAssignments()->where('program_id', '!=', $program->id)->exists()) {
                throw ValidationException::withMessages(['programId' => 'Only one program may be active at a time. Resolve the existing assignment before assigning another program.']);
            }
            $assignment = $senior->beneficiaries()->firstOrNew(['program_id' => $program->id]);
            if ($assignment->exists && $assignment->status === 'ACTIVE') {
                return $assignment;
            }
            $assignment->fill([
                'enrolled_on' => $data['enrolled_on'] ?? today()->toDateString(),
                'application_id' => $data['application_id'] ?? null,
                'status' => 'ACTIVE', 'assigned_by' => auth()->id(), 'assigned_at' => now(),
            ])->save();
            $senior->histories()->create(['changed_by' => auth()->id(), 'action' => 'benefit_assigned', 'changes' => ['program_id' => $program->id]]);
            BenefitAudit::record('beneficiary.enrolled', $assignment, ['program_id' => $program->id, 'senior_citizen_id' => $senior->id]);

            return $assignment;
        });
    }
}
