@props(['variant' => 'dark'])
<img src="{{ asset($variant === 'light' ? 'images/monogram-light.png' : 'images/monogram.png') }}" alt="" aria-hidden="true" {{ $attributes->merge(['class' => 'brand-monogram']) }}>
