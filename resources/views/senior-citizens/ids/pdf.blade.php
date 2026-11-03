<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">@include('senior-citizens.ids.styles')
    <style>@page { margin: 12mm; } body { margin: 0; } .print-sheet { border-collapse: collapse; table-layout: fixed; width: 175.2mm; } .print-sheet > tbody > tr { page-break-inside: avoid; } .print-sheet > tbody > tr > td { width: 85.6mm; padding: 0 4mm 4mm 0; vertical-align: top; } .print-sheet > tbody > tr > td + td { padding-right: 0; } .print-note { font-family: DejaVu Sans, sans-serif; font-size: 7pt; margin-top: 1mm; color: #444; }.batch-sheet { width: 175.2mm; border-collapse: collapse; table-layout: fixed; } .batch-sheet td { width: 85.6mm; padding: 0 4mm 4mm 0; vertical-align: top; } .batch-sheet td + td { padding-right: 0; } .batch-sheet tr { page-break-inside: avoid; } @media print { .id-sheet { break-after: page; page-break-after: always; } .id-sheet:last-of-type { break-after: auto; page-break-after: auto; } } </style>
</head><body>
    @if (($printLayout ?? 'single') === 'batch')
    <div class="id-sheet"><table class="batch-sheet"><tbody>@foreach (array_chunk($cards, 2) as $row)<tr>@foreach ($row as $card)<td><x-senior-id.front :card="$card" /><x-senior-id.back :card="$card" /></td>@endforeach</tr>@endforeach</tbody></table></div>
    @else
    <table class="print-sheet"><tbody>
        @foreach ($cards as $card)
            <tr><td><x-senior-id.front :card="$card" /></td><td><x-senior-id.back :card="$card" /></td></tr>
        @endforeach
    </tbody></table>
    @endif
    <p class="print-note">{{ ($printLayout ?? 'single') === 'batch' ? 'Up to four complete IDs per sheet, with front above back.' : 'Front and back shown side by side.' }}<br>85.60 x 53.98 mm per side. Print at actual size (100%); turn off fit-to-page. Cut and join before lamination.</p>
</body></html>
