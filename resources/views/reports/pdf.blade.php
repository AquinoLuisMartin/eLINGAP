<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} | eLINGAP</title>
    <style>
        @page { margin: 95px 30px 45px; }
        body { font-family: 'Inter', sans-serif; font-size: 9px; color: #000; line-height: 1.45; }
        header { position: fixed; top: -72px; left: 0; right: 0; height: 50px; border-bottom: 2px solid #000; padding-bottom: 9px; }
        h1 { font-size: 17px; margin: 0 0 6px; }
        p { margin: 0 0 6px; }
        .metadata { margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { border: 1px solid #d6d6d6; padding: 6px 5px; vertical-align: top; overflow-wrap: break-word; word-wrap: break-word; }
        th { background: #eee; text-align: left; font-size: 8px; }
        tbody tr:nth-child(even) { background: #f7f7f7; }
        .empty { text-align: center; padding: 18px; }
    </style>
</head>
<body>
    <header></header>
    <h1>{{ $title }}</h1>
    <div class="metadata">
        <p>Generated: {{ $generatedAt->format('F j, Y, g:i:s A e') }} | Matching records: {{ number_format($total) }}</p>
        <p>Filters: @forelse ($filterLabels as $label => $value){{ $label }}: {{ $value }}{{ $loop->last ? '' : ' / ' }}@empty All records @endforelse</p>
        @foreach ($summary ?? [] as $label => $value)<p>{{ $label }}: {{ $value }}</p>@endforeach
    </div>
    @php
        $widths = match ($type) {
            'beneficiaries' => [10, 12, 12, 11, 5, 4, 11, 17, 10, 8],
            'masterlist', 'milestones', 'archived' => [11, 16, 14, 14, 5, 11, 5, 14, 10],
            'applications' => [8, 8, 8, 8, 4, 9, 8, 10, 7, 6, 8, 8, 8],
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
</body>
</html>
