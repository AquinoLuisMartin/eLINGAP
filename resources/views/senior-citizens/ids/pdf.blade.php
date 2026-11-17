<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">@include('senior-citizens.ids.styles')
    <style>@page { margin: 12mm; } body { margin: 0; } .print-sheet { border-collapse: collapse; table-layout: fixed; width: 175.2mm; } .print-sheet > tbody > tr { page-break-inside: avoid; } .print-sheet > tbody > tr > td { width: 85.6mm; padding: 0 4mm 4mm 0; vertical-align: top; } .print-sheet > tbody > tr > td + td { padding-right: 0; } .print-note { font-family: DejaVu Sans, sans-serif; font-size: 7pt; margin-top: 1mm; color: #444; }
        .batch-sheet { width: 175.2mm; margin: 0 auto; border-collapse: collapse; }
        .batch-sheet tr { page-break-inside: avoid; }
        .batch-sheet td { width: 85.6mm; padding: 0 0 4mm; vertical-align: top; }
        .batch-sheet td.card-gap { width: 4mm; }
        .batch-sheet tr:last-child td { padding-bottom: 0; }
        .batch-page + .batch-page { page-break-before: always; }
        .batch-page .print-note { text-align: center; }
    </style>
</head><body>
    @if (($printLayout ?? 'single') === 'batch')
        @php
            $pageMargin = 12;
            $rowGap = 4;
            $noteHeight = 12;
            $cardHeight = 53.98;
            $rowsPerPage = max(1, (int) floor((($paperHeight ?? 297) - 2 * $pageMargin - $noteHeight + $rowGap) / ($cardHeight + $rowGap)));
        @endphp
        @foreach (array_chunk($cards, $rowsPerPage) as $pageCards)
            <div class="batch-page">
                <table class="batch-sheet"><tbody>
                    @foreach ($pageCards as $card)
                        <tr data-senior-id="{{ $card['senior']->id }}"><td><x-senior-id.front :card="$card" /></td><td class="card-gap"></td><td><x-senior-id.back :card="$card" /></td></tr>
                    @endforeach
                </tbody></table>
                <p class="print-note">Each row shows one senior's front and back side by side.<br>85.60 x 53.98 mm per side. Print at actual size (100%); turn off fit-to-page. Cut and join before lamination.</p>
            </div>
        @endforeach
    @else
    <table class="print-sheet"><tbody>
        @foreach ($cards as $card)
            <tr><td><x-senior-id.front :card="$card" /></td><td><x-senior-id.back :card="$card" /></td></tr>
        @endforeach
    </tbody></table>
    <p class="print-note">Front and back shown side by side.<br>85.60 x 53.98 mm per side. Print at actual size (100%); turn off fit-to-page. Cut and join before lamination.</p>
    @endif
</body></html>
