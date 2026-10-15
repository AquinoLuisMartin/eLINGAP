<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">@include('senior-citizens.ids.styles')
    <style>@page { margin: 12mm; } body { margin: 0; } .print-sheet { border-collapse: collapse; table-layout: fixed; width: 175.2mm; } .print-sheet tr { page-break-inside: avoid; } .print-sheet td { width: 85.6mm; padding: 0 4mm 4mm 0; vertical-align: top; } .print-sheet td + td { padding-right: 0; } .print-note { font-family: DejaVu Sans, sans-serif; font-size: 7pt; margin-top: 1mm; color: #444; }</style>
</head><body>
    <table class="print-sheet"><tbody>
        @foreach ($cards as $card)
            <tr><td><x-senior-id.front :card="$card" /></td><td><x-senior-id.back :card="$card" /></td></tr>
        @endforeach
    </tbody></table>
    <p class="print-note">Up to four IDs per sheet. Each row is one ID: front on the left, back on the right.<br>85.60 x 53.98 mm per side. Print at actual size (100%). Cut and join before lamination.</p>
</body></html>
