<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Certificate of Cancellation</title>
    <style>
        @page { margin: 25mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; line-height: 1.8; color: #111; }
        header, h1, .official { text-align: center; }
        h1 { font-size: 18pt; margin: 18mm 0 12mm; }
        .seal { width: 22mm; height: 22mm; }
        .official { margin-top: 22mm; }
        .signature { height: 15mm; max-width: 55mm; }
    </style>
</head>
<body>
    <header>@if($seal)<img class="seal" src="{{ $seal }}" alt="Municipal seal">@endif<br>Republic of the Philippines<br>{{ implode(', ', [$officeDetails['municipality'], $officeDetails['province']]) }}<br>{{ $officeDetails['office_name'] }}</header>
    <h1>Certificate of Cancellation</h1>
    <p>Reference: SC-TRANSFER-{{ $transfer->id }}</p>
    <p>This certifies that the local senior citizen registration of <strong>{{ $transfer->senior_name }}</strong>, registration number <strong>{{ $transfer->registration_number }}</strong>@if($transfer->osca_id_number), OSCA ID number <strong>{{ $transfer->osca_id_number }}</strong>@endif, was cancelled effective {{ $transfer->transferred_on->format('F j, Y') }} following a transfer of registration.</p>
    <p>Previous location: {{ $transfer->previous_location }}.</p>
    <p>Destination: {{ $transfer->destination }}.</p>
    <p>Requested by: {{ $transfer->requester_name }} ({{ $transfer->requested_by === 'SELF' ? 'senior citizen' : $transfer->requester_relationship }}).</p>
    <p>This certificate is issued for the transfer of registration to the destination locality.</p>
    <p>Date recorded: {{ $transfer->created_at->format('F j, Y') }}.</p>
    <div class="official">@if($signature)<img class="signature" src="{{ $signature }}" alt="OSCA Head signature"><br>@endif<strong>{{ $officials['osca_head_name'] }}</strong><br>OSCA Head</div>
</body>
</html>
