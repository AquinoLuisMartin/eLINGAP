<section class="page-intro"><div><p class="section-kicker">Administration</p><h2>System Configuration</h2><p class="section-subtitle">Stored organization and service information.</p></div></section>
<div class="panel p-5 mb-5">
    <form method="POST" action="{{ route('administration.configuration.update') }}" class="grid gap-4 sm:grid-cols-2">
        @csrf
        @method('PATCH')
        <p class="text-sm sm:col-span-2">Office details appear on public pages and official documents. Municipality and province are required for transfers and printing. Include the working days and any closures in office hours.</p>
        <x-alert />
        @foreach (\App\Models\SystemConfiguration::OFFICE_KEYS as $key)
            <label class="field-label" wire:key="office-setting-{{ $key }}">{{ ucfirst(str_replace('_', ' ', $key)) }}
                <input name="settings[{{ $key }}]" type="{{ $key === 'support_email' ? 'email' : 'text' }}" maxlength="{{ $key === 'office_address' ? 1000 : 180 }}" value="{{ old('settings.'.$key, $this->configuration->firstWhere('key', $key)?->value) }}">
                @error('settings.'.$key)<span class="text-sm text-red-700" role="alert">{{ $message }}</span>@enderror
            </label>
        @endforeach
        <div class="sm:col-span-2"><x-button type="submit">Save office settings</x-button></div>
    </form>
</div>
<div class="panel table-panel"><div class="table-scroll" role="region" aria-label="System settings" tabindex="0"><table><thead><tr><th>Setting</th><th>Stored value</th><th>Updated</th></tr></thead><tbody>
    @forelse ($this->configuration as $setting)
        <tr wire:key="setting-{{ $setting->id }}"><th scope="row">{{ ucfirst(str_replace('_', ' ', $setting->key)) }}</th><td>{{ $setting->value ?? 'Not configured' }}</td><td>{{ $setting->updated_at?->format('M j, Y g:i A') ?? 'Not recorded' }}</td></tr>
    @empty
        <tr><td colspan="3"><div class="empty-log-state"><strong>No configuration recorded</strong><span>Organization and service information will appear when stored.</span></div></td></tr>
    @endforelse
</tbody></table></div></div>
