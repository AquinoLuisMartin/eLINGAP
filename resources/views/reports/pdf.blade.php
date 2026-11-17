<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} | eLINGAP</title>
    <style>
        @page { margin: 95px 30px 45px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #243247; line-height: 1.45; }
        header { position: fixed; top: -72px; left: 0; right: 0; height: 50px; border-bottom: 2px solid #00327d; padding-bottom: 9px; }
        h1 { font-size: 17px; margin: 0 0 6px; }
        h2 { font-size: 12px; margin: 18px 0 6px; }
        p { margin: 0 0 6px; }
        .metadata { margin-bottom: 14px; color: #526174; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { border: 1px solid #d7dfe8; padding: 6px 5px; vertical-align: top; overflow-wrap: break-word; word-wrap: break-word; }
        th { background: #eaf0f7; text-align: left; font-size: 8px; }
        tbody tr:nth-child(even) { background: #f7f9fc; }
        .population { width: 70%; }
        .total { font-weight: bold; background: #eaf0f7; }
        .empty { text-align: center; padding: 18px; }
    </style>
</head>
<body>
    <header></header>
    <h1>{{ $title }}</h1>
    <div class="metadata">
        <p>Generated: {{ $generatedAt->format('F j, Y, g:i:s A e') }} | Matching records: {{ number_format($total) }}</p>
        <p>Filters: @forelse ($filterLabels as $label => $value){{ $label }}: {{ $value }}{{ $loop->last ? '' : ' / ' }}@empty All records @endforelse</p>
        @if ($type === 'beneficiaries')<p>Active program assignments for verified senior citizens. A senior may appear once for each assigned program.</p>@endif
    </div>
    @php
        $widths = match ($type) {
            'beneficiaries' => [10, 12, 12, 11, 5, 4, 11, 17, 10, 8],
            'masterlist', 'milestones', 'archived' => [11, 16, 14, 14, 5, 11, 5, 14, 10],
            'applications' => [10, 12, 11, 11, 5, 11, 11, 13, 9, 7],
            'payouts', 'proxy' => [10, 12, 11, 11, 5, 11, 15, 7, 11, 7],
            default => [65, 35],
        };
    @endphp
    <table>
        <thead><tr>@foreach ($headings as $heading)<th style="width: {{ $widths[$loop->index] }}%">{{ $heading }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse ($records as $record)
                <tr>@foreach ($report->row($type, $record) as $value)<td>{{ $value }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headings) }}" class="empty">No records match these filters.</td></tr>
            @endforelse
        </tbody>
    </table>
    <h2>Verified senior population at generation</h2>
    <p>Across all barangays, independent of report filters. Excludes deceased, archived and transferred records.</p>
    <table class="population">
        <thead><tr><th>Barangay</th><th>Verified senior population</th></tr></thead>
        <tbody>
            @foreach ($population as $barangay)<tr><td>{{ $barangay->name }}</td><td>{{ number_format($barangay->population) }}</td></tr>@endforeach
            <tr class="total"><td>Total verified senior population</td><td>{{ number_format($population->sum('population')) }}</td></tr>
        </tbody>
    </table>
</body>
</html>
