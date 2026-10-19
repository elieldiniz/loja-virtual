@props(['name' => 'point', 'metal' => 'gold'])
@include('partials.art.illustrations')
<svg viewBox="0 0 200 200" aria-hidden="true">{!! $illustrations[$name][$metal === 'silver' ? 'silver' : 'gold'] ?? '' !!}</svg>
