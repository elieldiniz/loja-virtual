@php
    $current = $current ?? 'dash';
    $nav = [
        ['dash', 'Painel', 'chart', route('admin.dashboard'), null],
        ['sales', 'Vendas', 'cash', route('admin.sales'), null],
        ['clients', 'Clientes', 'users', route('admin.customers'), \App\Models\Customer::count()],
        ['items', 'Produtos', 'gem', route('admin.products'), \App\Models\Product::count()],
        ['cats', 'Categorias', 'tag', route('admin.categories'), \App\Models\Category::count()],
        ['store', 'Loja', 'store', route('admin.store'), null],
    ];
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" href="{{ asset('favicon.png') }}">
    <title>{{ $title ?? 'Painel' }} · STyli Prates Admin</title>
    @include('partials.fonts')
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @livewireStyles
</head>
<body>
@include('partials.svg-defs')

<div class="shell">
    <aside class="rail">
        <div class="brand-mark"><x-brand variant="light" /></div>
        @foreach ($nav as [$key, $label, $icon, $url, $count])
            <a class="nav-btn" href="{{ $url }}" @if ($current === $key) aria-current="page" @endif>
                <x-icon :name="$icon" />{{ $label }}
                @if ($count !== null)<span class="count">{{ $count }}</span>@endif
            </a>
        @endforeach
        <div class="spacer"></div>
        <a class="nav-btn muted-btn" href="{{ url('/') }}" target="_blank" rel="noopener"><x-icon name="ext" />Ver o site</a>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button class="nav-btn muted-btn" type="submit"><x-icon name="out" />Sair</button>
        </form>
    </aside>

    <main class="main anim">
        <div class="topbar-m">
            <div class="brand-mark"><x-brand /></div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="icon-btn" type="submit" aria-label="Sair"><x-icon name="out" /></button>
            </form>
        </div>
        {{ $slot }}
    </main>
</div>

<nav class="tabbar" aria-label="Admin">
    @foreach ($nav as [$key, $label, $icon, $url, $count])
        <a class="tab" href="{{ $url }}" @if ($current === $key) aria-current="page" @endif><x-icon :name="$icon" />{{ $label }}</a>
    @endforeach
</nav>

<div class="toast" id="toast" role="status" aria-live="polite"
     x-data="{ show: false, message: '', warn: false, t: null }"
     x-on:toast.window="message = $event.detail.message; warn = !!$event.detail.warn; show = true; clearTimeout(t); t = setTimeout(() => show = false, 2200)"
     :class="{ 'show': show }">
    <template x-if="!warn"><x-icon name="check" /></template>
    <template x-if="warn"><x-icon name="alert" /></template>
    <span x-text="message"></span>
</div>

@livewireScripts
</body>
</html>
