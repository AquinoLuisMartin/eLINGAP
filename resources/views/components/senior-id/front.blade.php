@props(['card'])
@inject('typography', 'App\Services\SeniorCitizens\IdCardTypography')
@php
    $senior = $card['senior'];
    $address = $card['address'];
    $officeName = $card['office']['office_name'] ?? 'Office of the Senior Citizen Affairs';
    $municipality = mb_strtoupper(($card['office']['organization_name'] ?? null) ?: 'Municipality of Santa Maria');
    $contact = preg_replace('/^(?:\+63|0)(9\d{2})(\d{3})(\d{4})$/', '0$1-$2-$3', $senior->contact_number ?: ($senior->representative_contact ?? ''));
@endphp
<article class="id-card" aria-label="Front of senior citizen ID">
    @if ($card['seal'])<img src="{{ $card['seal'] }}" class="id-seal" alt="Municipal seal">@endif
    <header class="id-heading">
        <p class="id-republic">Republic of the Philippines</p>
        <p class="id-office" style="font-size: {{ $typography->fontSize($officeName, 36.2, 6.3, 4.0, 'DejaVu Serif', 'italic', false) }}pt">{{ $officeName }}</p>
        <p class="municipality" style="font-size: {{ $typography->fontSize($municipality, 36.2, 5.9, 3.8) }}pt">{{ $municipality }}</p>
    </header>
    <div class="id-photo"><span>Attach physical photo<br><small>1 x 1 inch<br>2.54 x 2.54 cm</small></span></div>
    <div class="id-number"><span>OSCA ID No.</span> <strong>{{ $senior->osca_id_number ?? 'On issuance' }}</strong></div>
    <section class="id-personal">
        <p class="id-label">NAME:</p>
        <p class="name" style="font-size: {{ $typography->fontSize($senior->full_name, 71.2, 7.8, 3.5) }}pt">{{ $senior->full_name }}</p>
    </section>
    <div class="id-address"><p class="id-label">ADDRESS:</p><p class="address" style="font-size: {{ $address['fontSize'] }}pt">@if ($address['fits'])@foreach ($address['lines'] as $line){{ $line }}@if (!$loop->last)<br>@endif@endforeach @else Address needs review before printing. @endif</p></div>
    <div class="id-contact"><span class="id-contact-label">Contact No.:</span><strong class="id-contact-value">{{ $contact }}</strong></div>
    <div class="id-dates">
        <div class="id-birth"><p class="id-date-value">{{ $senior->birth_date->format('M d, Y') }}</p><p class="id-label">Date of Birth</p></div>
        <div class="id-age"><p class="id-date-value">{{ $card['age'] }}</p><p class="id-label">Age</p></div>
        <div class="id-sex"><p class="id-date-value">{{ ucfirst(strtolower($senior->sex)) }}</p><p class="id-label">Sex</p></div>
        <div class="id-issuance"><p class="id-date-value">{{ $senior->id_generated_at?->format('M d, Y') ?? 'Upon approval' }}</p><p class="id-label">Date of Issuance</p></div>
    </div>
    <p class="id-validity">THIS CARD IS NON-TRANSFERABLE AND VALID ANYWHERE IN THE PHILIPPINES</p>
    <div class="id-thumbprint">
        <p class="id-holder-name" style="font-size: {{ $typography->fontSize($senior->full_name, 80.2, 6.0, 2.4) }}pt">{{ $senior->full_name }}</p>
        <span class="id-thumbprint-label">Printed Name and Physical Thumbprint / Thumbmark</span>
    </div>
    @if ($card['draft'] && ! $senior->id_generated_at)<span class="id-draft">DRAFT - NOT ISSUED</span>@endif
</article>
