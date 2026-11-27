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
            @if ($valid)
                <dl class="mt-6 grid gap-4 text-sm">
                    <div>
                        <dt class="text-slate-600">Name</dt>
                        <dd class="mt-1 break-words font-semibold">{{ $seniorName }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-600">OSCA ID No.</dt>
                        <dd class="mt-1 break-words font-semibold">{{ $oscaIdNumber }}</dd>
                    </div>
                </dl>
            @endif
            <div class="mt-8 border-t border-slate-200 pt-6 text-sm">
                <p class="text-slate-600">Issuing office</p>
                <p class="mt-1 font-semibold">OSCA Sta. Maria</p>
                @if (filled($officeDetails['organization_name']))
                    <p class="mt-1">{{ $officeDetails['organization_name'] }}</p>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
