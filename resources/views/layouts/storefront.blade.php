@php
    $store = \App\Models\StoreSetting::current();
    $store->load(['promoPhrases', 'chatShortcuts']);
    $links = new \App\Services\Storefront\StorefrontLinks($store);
    $messages = app(\App\Services\MessageBuilder::class);
    $welcome = $store->welcome_message ?: 'Oi! Quer ajuda pra escolher? Fala com a gente 😊';
    $shortcuts = $store->chatShortcuts->map(fn ($s) => [
        'label' => $s->label,
        'gift' => $s->opens_gift_section,
        'url' => $s->opens_gift_section ? null : $links->whatsapp($s->whatsapp_message ?: 'Oi! '.$s->label),
    ])->values()->all();
    $hoursLine = collect([
        $store->hours_weekdays ? 'Seg a sex · '.$store->hours_weekdays : null,
        $store->hours_saturday ? 'Sáb · '.$store->hours_saturday : null,
    ])->filter()->implode(' · ');
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title>{{ $title ?? 'STyli Prates' }}</title>
    <meta name="description" content="Semijoias delicadas para usar todo dia e para presentear, com atendimento de verdade pelo WhatsApp.">
    <meta property="og:title" content="STyli Prates">
    <meta property="og:description" content="Semijoias delicadas para usar todo dia e para presentear. Monte sua sacola e fale com a gente no WhatsApp.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ asset('images/logo.png') }}">
    <link rel="icon" href="{{ asset('favicon.png') }}">
    @include('partials.fonts')
    @vite(['resources/css/storefront.css', 'resources/js/storefront.js'])
    @livewireStyles
</head>
<body>
@include('partials.svg-defs')

{{ $slot }}

@include('storefront.partials.overlays', ['store' => $store, 'links' => $links, 'messages' => $messages, 'welcome' => $welcome, 'shortcuts' => $shortcuts, 'hoursLine' => $hoursLine])

@livewireScripts
</body>
</html>
