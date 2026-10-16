<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>eLINGAP</title>
    <x-favicon />@vite('resources/css/app.css')@include('senior-citizens.ids.styles')</head>
<body class="bg-slate-50 p-6 font-sans text-slate-800">
    <main class="mx-auto max-w-4xl space-y-8">
        <header><a href="{{ route('senior-id.show', $card['senior']) }}" class="text-osca-primary underline">Back to ID preparation</a><h1 class="mt-4 text-2xl font-medium">Front and back preview</h1><p class="mt-2 text-sm text-slate-500">Review only. Use the print-ready PDF to print at the selected paper size.</p></header>
        <div class="id-workspace-preview grid gap-8 overflow-x-auto md:grid-cols-2">
            <figure><figcaption class="mb-4 text-sm">Front</figcaption><x-senior-id.front :card="$card" /></figure>
            <figure><figcaption class="mb-4 text-sm">Back</figcaption><x-senior-id.back :card="$card" /></figure>
        </div>
    </main>
</body></html>
