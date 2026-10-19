<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eLINGAP</title>
    <x-favicon />
    {{-- Apply saved theme before paint to avoid light/dark flash --}}
    <script>
        (function () {
            var theme = 'light';
            try { theme = localStorage.getItem('elingap-theme') === 'dark' ? 'dark' : 'light'; } catch {}
            document.documentElement.classList.add(theme === 'dark' ? 'theme-dark' : 'theme-light');
            document.documentElement.style.colorScheme = theme;
        })();
    </script>
    @vite(['resources/css/admin.css'])
    @livewireStyles
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <livewire:administration.dashboard />

    @livewireScripts
</body>
</html>
