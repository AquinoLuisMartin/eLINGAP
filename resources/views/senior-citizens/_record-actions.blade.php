@can('declareDeceased', $seniorCitizen)
    @if ($seniorCitizen->status !== \App\Enums\SeniorCitizenStatus::Deceased)
        <x-benefit-panel>
            <details>
                <summary class="cursor-pointer text-sm font-medium text-osca-danger dark:text-red-300">Declare deceased</summary>
                <p class="mt-3 max-w-2xl text-sm text-slate-500">This declaration stops future releases and requires a death document. Confirm the record identifier before saving.</p>
                <form method="POST" action="{{ route('senior-citizens.death.store', $seniorCitizen) }}" enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-2" onsubmit="return confirm('Declare this senior deceased?')">
                    @csrf
                    <label class="field-label mt-0!">Date of death
                        <input type="date" name="died_on" required max="{{ today()->format('Y-m-d') }}">
                    </label>
                    <label class="field-label mt-0!">Death certificate or supporting document
                        <input type="file" name="death_document" accept=".pdf,.jpg,.jpeg,.png" required>
                    </label>
                    <label class="field-label mt-0! sm:col-span-2">Type {{ $seniorCitizen->osca_id_number ?: $seniorCitizen->registration_number }} to confirm
                        <input name="confirmation" required autocomplete="off">
                    </label>
                    <div class="sm:col-span-2"><button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-osca-danger px-4 py-3 text-sm text-white hover:bg-osca-danger/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-danger"><x-admin.icon name="alert-triangle" size="16" /> Record declaration</button></div>
                </form>
            </details>
        </x-benefit-panel>
    @endif
@endcan

@can('correctDeath', $seniorCitizen)
    @if ($seniorCitizen->status === \App\Enums\SeniorCitizenStatus::Deceased)
        <x-benefit-panel>
            <h3 class="text-lg font-semibold">Correct death declaration</h3>
            <form method="POST" action="{{ route('senior-citizens.death.correct', $seniorCitizen) }}" class="mt-4 space-y-4" onsubmit="return confirm('Correct this death declaration and return the record to pending verification?')">
                @csrf
                @method('PATCH')
                <label class="field-label mt-0!">Correction reason
                    <textarea name="reason" required minlength="10" rows="3"></textarea>
                </label>
                <button type="submit" class="primary-button">Correct declaration</button>
            </form>
        </x-benefit-panel>
    @endif
@endcan

@can('delete', $seniorCitizen)
    <x-benefit-panel>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div><h3 class="text-lg font-semibold">Archive record</h3><p class="mt-1 text-sm text-slate-500">Move this record out of the active masterlist.</p></div>
            <form method="POST" action="{{ route('administration.senior-citizens.destroy', $seniorCitizen) }}" onsubmit="return confirm('Archive this senior citizen record?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="secondary-button w-full text-osca-danger! dark:text-red-300! sm:w-auto"><x-admin.icon name="archive" size="16" /> Archive record</button>
            </form>
        </div>
    </x-benefit-panel>
@endcan
