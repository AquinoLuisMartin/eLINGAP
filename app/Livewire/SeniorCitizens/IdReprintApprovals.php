<?php

namespace App\Livewire\SeniorCitizens;

use App\Models\IdReprintRequest;
use App\Models\SeniorCitizen;
use App\Services\SeniorCitizens\IdReprints;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class IdReprintApprovals extends Component
{
    use WithPagination;

    public array $reviewReasons = [];

    public string $notice = '';

    public function boot(): void
    {
        Gate::authorize('approveIdReprints', SeniorCitizen::class);
    }

    public function review(IdReprints $reprints, int $requestId, bool $approve): void
    {
        $reprints->review(IdReprintRequest::findOrFail($requestId), $approve, $this->reviewReasons[$requestId] ?? '');
        unset($this->reviewReasons[$requestId]);
        $this->resetValidation();
        $this->notice = $approve ? 'Reprint approved for one successful physical print.' : 'Reprint request rejected. Printing remains locked.';
    }

    public function render()
    {
        return view('livewire.senior-citizens.id-reprint-approvals', [
            'requests' => IdReprintRequest::with(['seniorCitizen', 'requester', 'reviewer'])->orderByRaw("CASE WHEN status = 'PENDING' THEN 0 ELSE 1 END")->latest('id')->paginate(15),
        ]);
    }
}
