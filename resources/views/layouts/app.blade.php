<!DOCTYPE html>
<html lang="en" class="bg-slate-50" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }" x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'GabayDocs | OSCA Staff Portal' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <div class="flex min-h-screen">
        @include('components.sidebar', ['active' => $active ?? 'Masterlist'])
        <main class="ml-16 flex-1">
            @include('components.header', ['title' => $headerTitle ?? 'Senior Records Registry', 'eyebrow' => $headerEyebrow ?? 'Registry Management', 'user' => $user ?? ['name' => 'Maria A.', 'role' => 'OSCA Staff']])
            @yield('content')
        </main>
    </div>
</body>
</html>
