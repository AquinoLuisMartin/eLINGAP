<section class="page-intro"><div><p class="section-kicker">Administration</p><h2>System Configuration</h2><p class="section-subtitle">Stored organization and service information.</p></div></section>
<div class="panel table-panel"><div class="table-scroll"><table><thead><tr><th>Setting</th><th>Stored value</th><th>Updated</th></tr></thead><tbody>
    @forelse ($this->configuration as $setting)
        <tr wire:key="setting-{{ $setting->id }}"><th scope="row">{{ ucfirst(str_replace('_', ' ', $setting->key)) }}</th><td>{{ $setting->value ?? 'Not configured' }}</td><td>{{ $setting->updated_at?->format('M j, Y g:i A') ?? 'Not recorded' }}</td></tr>
    @empty
        <tr><td colspan="3"><div class="empty-log-state"><strong>No configuration recorded</strong><span>Organization and service information will appear when stored.</span></div></td></tr>
    @endforelse
</tbody></table></div></div>
