<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">@include('senior-citizens.ids.styles')
    <style>@page { margin: 12mm; } body { margin: 0; } .print-side { margin: 0 0 5mm; page-break-inside: avoid; } .print-note { font-family: DejaVu Sans, sans-serif; font-size: 7pt; margin-top: 5mm; color: #444; }</style>
</head><body>
    <div class="print-side"><x-senior-id.front :card="$card" /></div>
    <div class="print-side"><x-senior-id.back :card="$card" /></div>
    <p class="print-note">85.60 x 53.98 mm per side. Print at actual size (100%). Cut and join front and back before lamination.</p>
</body></html>
