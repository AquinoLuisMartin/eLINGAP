@props(['card'])
@php
    $senior = $card['senior'];
    $address = $senior->address.', '.$senior->barangay->name.', Santa Maria, Bulacan';
@endphp
<article class="id-card" aria-label="Front of senior citizen ID">
    @if ($card['seal'])<img src="{{ $card['seal'] }}" class="id-seal" alt="Municipal seal">@endif
    <header class="id-heading">
        <p class="id-republic">Republic of the Philippines</p>
        <p class="id-office">Office for Senior Citizens Affairs</p>
        <p class="municipality">MUNICIPALITY OF SANTA MARIA</p>
    </header>
    <div class="id-photo"><span>Attach physical photo<br><small>1 x 1 inch<br>2.54 x 2.54 cm</small></span></div>
    <div class="id-number"><span>OSCA ID No.</span> <strong>{{ $senior->osca_id_number ?? 'On issuance' }}</strong></div>
    <section class="id-personal">
        <p class="id-label">NAME:</p>
        <p @class(['name', 'medium-name' => mb_strlen($senior->full_name) > 30, 'compact-name' => mb_strlen($senior->full_name) > 60, 'long-name' => mb_strlen($senior->full_name) > 100])>{{ $senior->full_name }}</p>
    </section>
    <div class="id-address"><p class="id-label">ADDRESS:</p><p @class(['address', 'compact-address' => mb_strlen($address) > 100])>{{ $address }}</p></div>
    <div class="id-contact"><span class="id-contact-label">Contact No.:</span><strong class="id-contact-value">{{ $senior->contact_number ?: $senior->representative_contact }}</strong></div>
    <div class="id-dates">
        <div class="id-birth"><p class="id-date-value">{{ $senior->birth_date->format('M d, Y') }}</p><p class="id-label">Date of Birth</p></div>
        <div class="id-age"><p class="id-date-value">{{ $card['age'] }}</p><p class="id-label">Age</p></div>
        <div class="id-sex"><p class="id-date-value">{{ ucfirst(strtolower($senior->sex)) }}</p><p class="id-label">Sex</p></div>
        <div class="id-issuance"><p class="id-date-value">{{ $senior->id_generated_at?->format('M d, Y') ?? 'Upon approval' }}</p><p class="id-label">Date of Issuance</p></div>
    </div>
    <p class="id-validity">THIS CARD IS NON-TRANSFERABLE AND VALID ANYWHERE IN THE PHILIPPINES</p>
    <div class="id-thumbprint">
        <p @class(['id-holder-name', 'compact-name' => mb_strlen($senior->full_name) > 60, 'long-name' => mb_strlen($senior->full_name) > 100])>{{ $senior->full_name }}</p>
        <span class="id-thumbprint-label">Printed Name and Physical Thumbprint / Thumbmark</span>
    </div>
    @if ($card['draft'] && ! $senior->id_generated_at)<span class="id-draft">DRAFT - NOT ISSUED</span>@endif
</article>
