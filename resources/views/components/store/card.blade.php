@props(['card', 'index' => 0])
@php $id = $card->id; @endphp
<article class="card anim" style="--i:{{ $index }}" wire:key="card-{{ $id }}">
    <div style="position:relative;overflow:hidden">
        <button class="pic" type="button" x-on:click="$store.ui.openProduct({{ $id }})" aria-label="Ver {{ $card->name }}">
            <span class="face a">@if ($card->coverUrl)<img src="{{ $card->coverUrl }}" alt="{{ $card->name }}" loading="lazy">@endif</span>
            @if ($card->hoverUrl)<span class="face b"><img src="{{ $card->hoverUrl }}" alt="" loading="lazy"></span>@endif
        </button>
        @if ($card->badge)<span class="tag {{ $card->badge === 'Novo' ? 'new' : '' }}">{{ $card->badge }}</span>@endif
        <button class="fav" type="button" x-on:click="$store.favs.toggle({{ $id }})" :aria-pressed="$store.favs.has({{ $id }}) ? 'true' : 'false'" aria-pressed="false" aria-label="Favoritar {{ $card->name }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg>
        </button>
        <div class="quick"><button class="btn btn-dark" type="button" x-on:click="$store.bag.add({{ $id }}, @js($card->name))">Quero essa</button></div>
    </div>
    <h3>{{ $card->name }}</h3>
    <div class="prices">@if ($card->oldPrice)<s>{{ $card->oldPrice->format() }}</s>@endif<strong>{{ $card->price->format() }}</strong></div>
    <span class="inst">ou {{ $card->installments }}x de {{ $card->installmentAmount->format() }} sem juros</span>
    <span class="pix">{{ $card->pixPrice->format() }} no Pix</span>
    <button class="btn btn-line add-m" type="button" x-on:click="$store.bag.add({{ $id }}, @js($card->name))">Quero essa</button>
</article>
