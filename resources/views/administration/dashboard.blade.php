<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eLINGAP — System Admin Dashboard</title>
    <link rel="icon" type="image/png" href="{{ asset('images/eLINGAP.png') }}">
    {{-- Apply saved theme before paint to avoid light/dark flash --}}
    <script>
        (function () {
            var theme = localStorage.getItem('elingap-theme') === 'dark' ? 'dark' : 'light';
            document.documentElement.classList.add(theme === 'dark' ? 'theme-dark' : 'theme-light');
            document.documentElement.style.colorScheme = theme;
        })();
    </script>
    @vite(['resources/css/admin.css'])
    @livewireStyles
</head>
<body>
    <livewire:administration.dashboard />

    @livewireScripts
</body>
</html>
