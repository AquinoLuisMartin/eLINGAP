<div class="office-configuration">
<section class="page-intro"><div><p class="section-kicker">Administration</p><h2>System Configuration</h2></div></section>
@php
    $defaults = ['municipality' => 'Santa Maria', 'province' => 'Bulacan'];
    $placeholders = [
        'organization_name' => 'eLINGAP Senior Citizen Information System',
        'office_name' => 'Office of the Senior Citizens Affairs (OSCA)',
        'municipality' => 'Santa Maria',
        'province' => 'Bulacan',
        'support_email' => 'e.g., osca.santamaria@bulacan.gov.ph',
        'support_phone' => 'e.g., (044) 123-4567 or 09XX-XXX-XXXX',
        'office_address' => 'e.g., Municipal Hall Compound, Santa Maria, Bulacan',
        'office_hours' => 'e.g., Mon–Fri, 8:00 AM – 5:00 PM',
    ];
@endphp
<div class="panel office-settings-card">
    <form method="POST" action="{{ route('administration.configuration.update') }}" class="grid gap-3 sm:grid-cols-2">
        @csrf
        @method('PATCH')
        <x-alert />
        @foreach (\App\Models\SystemConfiguration::OFFICE_KEYS as $key)
            @php($saved = $this->configuration->firstWhere('key', $key)?->value)
            <label class="field-label" wire:key="office-setting-{{ $key }}"><span class="office-setting-label">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                <input name="settings[{{ $key }}]" type="{{ match ($key) { 'support_email' => 'email', 'support_phone' => 'tel', default => 'text' } }}" maxlength="{{ $key === 'office_address' ? 1000 : 180 }}" value="{{ old('settings.'.$key, filled($saved) ? $saved : ($defaults[$key] ?? '')) }}" placeholder="{{ $placeholders[$key] }}" class="h-9 min-h-9 w-full rounded-md border border-[var(--line)] bg-[var(--white)] px-3 py-1.5 text-xs text-[var(--ink)] placeholder:text-[var(--muted)] focus:border-blue-600 focus:outline-2 focus:outline-offset-2 focus:outline-blue-600 dark:bg-[var(--workspace-input)]">
                @error('settings.'.$key)<span class="text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</span>@enderror
            </label>
        @endforeach
        <div class="sm:col-span-2"><x-button type="submit" class="office-settings-save">Save office settings</x-button></div>
    </form>
</div>
<div class="panel table-panel"><div class="table-scroll" role="region" aria-label="System settings" tabindex="0"><table><thead><tr><th>Setting</th><th>Stored value</th><th>Updated</th></tr></thead><tbody>
    @forelse ($this->configuration as $setting)
        <tr wire:key="setting-{{ $setting->id }}"><th scope="row">{{ ucfirst(str_replace('_', ' ', $setting->key)) }}</th><td>{{ $setting->value ?? 'Not configured' }}</td><td>{{ $setting->updated_at?->format('M j, Y g:i A') ?? 'Not recorded' }}</td></tr>
    @empty
        <tr><td colspan="3"><div class="empty-log-state"><strong>No configuration recorded</strong><span>Organization and service information will appear when stored.</span></div></td></tr>
    @endforelse
</tbody></table></div></div>
</div>
