<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>eLINGAP</title>
    <x-favicon />@vite('resources/css/app.css')</head>
<body class="bg-slate-50 font-sans text-slate-800">
    <main class="mx-auto flex min-h-screen max-w-xl items-center px-5 py-12">
        <section class="w-full rounded-xl border border-slate-200 bg-white p-8 text-center sm:p-12" aria-labelledby="verification-status">
            <img src="{{ asset('images/eLINGAP.png') }}" class="mx-auto size-16 object-contain" alt="eLINGAP">
            <p class="mt-6 text-sm text-slate-500">Senior citizen ID verification</p>
            <h1 id="verification-status" class="mt-3 text-3xl font-medium {{ $valid ? 'text-emerald-700' : 'text-slate-700' }}">{{ $valid ? 'Verified' : 'Not valid' }}</h1>
            <p class="mt-4 text-sm">{{ $valid ? 'This ID is recognized by eLINGAP.' : 'This ID cannot be verified as valid. Contact the issuing office for assistance.' }}</p>
            <p class="mt-8 border-t pt-6 text-sm">{{ $officeDetails['office_name'] ?? 'Issuing office not configured' }}<br>{{ $officeDetails['organization_name'] }}</p>
            <p class="mt-6 text-xs text-slate-500">Personal information is protected and is not displayed on this page.</p>
        </section>
    </main>
</body>
</html>
