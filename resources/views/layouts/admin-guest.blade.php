<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" href="{{ asset('favicon.png') }}">
    <title>{{ $title ?? 'Entrar' }} · STyli Prates Admin</title>
    @include('partials.fonts')
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @livewireStyles
</head>
<body>
{{ $slot }}
@livewireScripts
</body>
</html>
