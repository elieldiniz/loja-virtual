@props(['variant' => 'dark'])
<img src="{{ asset($variant === 'light' ? 'images/logo-light.png' : 'images/logo.png') }}" alt="STyli Prates" {{ $attributes->merge(['class' => 'brand-logo']) }}>
