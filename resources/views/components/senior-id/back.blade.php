@props(['card'])
<article class="id-card" aria-label="Back of senior citizen ID">
    <section class="id-benefits"><h2>Benefits and Privileges under Republic Act No. 9994</h2><ul>
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
    @if($card['qr'])<img src="{{ $card['qr'] }}" class="id-qr" alt="eLINGAP verification QR"><p class="id-qr-label">Scan to verify with eLINGAP</p>@else<div class="id-qr" style="border: 0.15mm dashed #777; font-size: 5pt; text-align: center; padding-top: 6mm;">QR assigned on issuance</div>@endif
    <table class="id-officials"><tr><td>@if($card['headSignature'])<img src="{{ $card['headSignature'] }}" alt="OSCA Head signature">@else<div class="signature-space"></div>@endif</td><td>@if($card['mayorSignature'])<img src="{{ $card['mayorSignature'] }}" alt="Mayor signature">@else<div class="signature-space"></div>@endif</td></tr><tr><td class="official-name">{{ $card['officials']['osca_head_name'] ?? '' }}</td><td class="official-name">{{ $card['officials']['municipal_mayor_name'] ?? '' }}</td></tr><tr><td>OSCA HEAD</td><td>MUNICIPAL MAYOR</td></tr></table>
</article>
