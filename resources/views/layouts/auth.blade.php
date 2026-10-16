<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | eLINGAP</title>
    <x-favicon />
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-50 text-slate-900">
    <main class="flex min-h-screen items-center justify-center p-4">
        <section class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="auth-heading">
            <a href="{{ route('home') }}" class="text-sm font-semibold text-blue-700 underline">eLINGAP home</a>
            <h1 id="auth-heading" class="mt-4 text-2xl font-semibold">@yield('title')</h1>
            <div class="mt-4"><x-alert /></div>
            @yield('content')
            <a href="{{ route('login') }}" class="mt-5 inline-block text-sm text-blue-700 underline">Back to sign in</a>
        </section>
    </main>
</body>
</html>
