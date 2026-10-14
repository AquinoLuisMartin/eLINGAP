@props(['card'])
<article class="id-card" aria-label="Front of senior citizen ID">
    @if($card['seal'])<img src="{{ $card['seal'] }}" class="id-seal" alt="Municipal seal">@endif
    <header class="id-heading"><p>Republic of the Philippines</p><p>Office for Senior Citizens Affairs</p><p class="municipality">MUNICIPALITY OF SANTA MARIA</p><p class="validity">Non-transferable. Valid anywhere in the Philippines.</p></header>
    <p class="id-number">OSCA ID No.<br>{{ $card['senior']->osca_id_number ?? 'Assigned on issuance' }}</p>
    @if($card['photo'])<img src="{{ $card['photo'] }}" class="id-photo" alt="ID photo">@else<div class="id-photo"></div>@endif
    <div class="id-personal"><p class="name">NAME: {{ $card['senior']->full_name }}</p><p class="address">ADDRESS: {{ $card['senior']->address }}, {{ $card['senior']->barangay->name }}, Santa Maria, Bulacan</p><p class="contact">Contact No.: {{ $card['senior']->contact_number ?: $card['senior']->representative_contact }}</p></div>
    <table class="id-dates"><tr><td class="value">{{ $card['senior']->birth_date->format('m/d/Y') }}</td><td class="value">{{ $card['age'] }}</td><td class="value">{{ ucfirst(strtolower($card['senior']->sex)) }}</td><td class="value">{{ $card['senior']->id_generated_at?->format('m/d/Y') ?? 'Not issued' }}</td></tr><tr><td>Date of Birth</td><td>Age</td><td>Sex</td><td>Date of Issuance</td></tr></table>
    <div class="id-signing">@if($card['signature'])<img src="{{ $card['signature'] }}" alt="Senior signature">@else<div class="signature-space"></div>@endif<p class="printed-name">{{ $card['senior']->full_name }}</p><p>Printed Name and Signature</p></div>
    @if($card['draft'] && !$card['senior']->id_generated_at)<span class="id-draft">DRAFT - NOT ISSUED</span>@endif
</article>
