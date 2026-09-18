<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eLINGAP — System Admin Dashboard</title>
    <link rel="icon" type="image/png" href="{{ asset('images/eLINGAP.png') }}">
    @vite(['resources/css/admin.css'])
    @livewireStyles
</head>
<body>
    <livewire:administration.dashboard />

    @livewireScripts
</body>
</html>
