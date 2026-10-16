@props(['card'])
<article class="id-card" aria-label="Back of senior citizen ID">
    <header class="id-back-heading"><h2>Benefits and Privileges under Republic Act No. 9994</h2></header>
    <section class="id-benefits" aria-label="Benefits and Privileges under Republic Act No. 9994"><ul>
        <li>Free medical/dental, diagnostic and laboratory services in government facilities</li>
        <li>20% discount for medicines</li>
        <li>20% discount in hotels, restaurants and recreation centers</li>
        <li>20% discount in theaters, cinema houses and concert halls</li>
        <li>20% discount in medical/dental services, diagnostic and laboratory fees in private facilities</li>
        <li>20% discount in fare for domestic air, sea travel and public land transportation</li>
        <li>5% discount in basic necessities and prime commodities</li>
        <li>12% VAT exemption on goods and services entitled to the 20% discount</li>
        <li>5% discount for monthly water and electricity utilization, provided the meter is under the senior citizen's name</li>
    </ul></section>
    <div class="id-warning"><p>Persons and corporations violating RA 9994 shall be penalized.</p><p>Only for the exclusive use of senior citizens. Abuse of privileges is punishable by law.</p></div>
    <div class="id-officials">
        <div class="id-official">
            <div class="signature-space">@if($card['headSignature'])<img src="{{ $card['headSignature'] }}" alt="OSCA Head signature">@endif</div>
            <p class="official-name">{{ $card['officials']['osca_head_name'] ?? '' }}</p><p class="official-role">OSCA HEAD</p>
        </div>
        <div class="id-official">
            <div class="signature-space">@if($card['mayorSignature'])<img src="{{ $card['mayorSignature'] }}" alt="Mayor signature">@endif</div>
            <p class="official-name">{{ $card['officials']['municipal_mayor_name'] ?? '' }}</p><p class="official-role">MUNICIPAL MAYOR</p>
        </div>
    </div>
    @if($card['qr'])<img src="{{ $card['qr'] }}" class="id-qr" alt="eLINGAP verification QR">@else<div class="id-qr id-qr-placeholder">QR assigned<br>on issuance</div>@endif
    <p class="id-qr-label">Scan to verify with eLINGAP</p>
</article>
