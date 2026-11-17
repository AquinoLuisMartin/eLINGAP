@can('configureId', \App\Models\SeniorCitizen::class)
    <dialog id="printing-completion" class="confirm-dialog modal-card" aria-labelledby="printing-completion-title" aria-describedby="printing-completion-description" data-pending-url="{{ route('senior-id.printing.pending') }}" data-complete-url="{{ route('senior-id.printing.complete') }}">
        <form data-print-results class="flex flex-col gap-4">
            @csrf
            <div>
                <h2 id="printing-completion-title">Record printing results</h2>
                <p id="printing-completion-description" class="text-sm text-slate-500 dark:text-slate-400">Check the physical print, then choose one result for each ID. Opening the PDF does not mark an ID as printed.</p>
            </div>
            <div data-print-result-rows class="flex max-h-[50dvh] flex-col gap-3 overflow-y-auto"></div>
            <p class="text-xs!">Still printing? Choose Later and return through Printing Completion. Your pending IDs will remain available.</p>
            <div class="modal-actions">
                <button type="button" data-print-later class="secondary-button">Later</button>
                <button type="submit" data-print-save class="primary-button disabled:cursor-not-allowed disabled:opacity-50" disabled>Save Results</button>
            </div>
        </form>
    </dialog>
@endcan
