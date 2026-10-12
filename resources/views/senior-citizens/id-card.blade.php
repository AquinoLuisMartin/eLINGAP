<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OSCA Senior Citizen ID</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-slate-100 p-6 text-slate-900 print:bg-white print:p-0">
    <div class="mb-5 flex gap-4 print:hidden">
        <button onclick="window.print()" class="rounded bg-osca-primary px-4 py-2 text-white">Print ID</button>
        <a href="{{ route('senior-citizens.show', $senior) }}" class="rounded border px-4 py-2">Back to profile</a>
    </div>
    <article class="max-w-lg rounded-xl border-2 border-osca-primary bg-white p-5">
        <header class="mb-5 flex items-center gap-3 border-b pb-3">
            <img src="{{ asset('images/eLINGAP.png') }}" alt="eLINGAP" class="size-14 object-contain">
            <div><p class="font-semibold">Office of the Senior Citizen Affairs</p><p class="text-sm">Municipality of Sta. Maria, Bulacan</p><h1 class="mt-1 font-bold text-osca-primary">Senior Citizen Identification Card</h1></div>
        </header>
        <div class="flex gap-4">
            @if ($hasPhoto)<img src="{{ route('senior-citizens.photo', $senior) }}" alt="Senior ID photo" class="size-28 rounded border object-cover">@endif
            <dl class="space-y-2 text-sm">
                <div><dt class="text-slate-500">Name</dt><dd class="text-lg font-bold">{{ $senior->full_name }}</dd></div>
                <div><dt class="text-slate-500">OSCA ID</dt><dd class="font-semibold">{{ $senior->osca_id_number }}</dd></div>
                <div><dt class="text-slate-500">Date of birth</dt><dd>{{ $senior->birth_date->format('F j, Y') }}</dd></div>
                <div><dt class="text-slate-500">Barangay</dt><dd>{{ $senior->barangay->name }}</dd></div>
                <div><dt class="text-slate-500">Issued</dt><dd>{{ $senior->id_generated_at->format('F j, Y') }}</dd></div>
            </dl>
        </div>
    </article>
</body>
</html>
