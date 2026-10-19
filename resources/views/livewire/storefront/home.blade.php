@php
    $phrases = $settings->promoPhrases->pluck('phrase')->values()->all();
    $pix = $pricing->pixDiscountPercent;
    $inst = $pricing->maxInstallments;
    $cityLine = collect([$settings->city, $settings->state])->filter()->implode('/');
    $addressLine = collect([$settings->address, $settings->city ? $settings->city.($settings->state ? '/'.$settings->state : '') : null])->filter()->implode(' · ');
    $instagram = $settings->instagram;
    $instagramUrl = $instagram ? 'https://instagram.com/'.ltrim($instagram, '@') : null;
    $warranty = $settings->plating_warranty;
    $conjuntos = $categories->first(fn ($c) => \App\Support\SearchText::normalize($c->name) === 'conjuntos');
    $scrollToShop = "document.getElementById('shop')?.scrollIntoView({behavior:'smooth',block:'start'})";
@endphp
<div id="top">
@if ($phrases)
    <div class="promo" id="promo" x-data="{ i: 0, phrases: @js($phrases) }" x-init="if (phrases.length > 1 && !matchMedia('(prefers-reduced-motion: reduce)').matches) setInterval(() => i = (i + 1) % phrases.length, 4000)" x-text="phrases[i]">{{ $phrases[0] }}</div>
@endif

<header class="site">
    <div class="wrap head-row" x-data="{ mobileSearch: false }">
        <div class="search">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg>
            <label class="sr" for="q">Buscar peças</label>
            <input id="q" type="search" placeholder="Buscar peças" wire:model.live.debounce.250ms="q" x-on:keydown.enter="{{ $scrollToShop }}">
        </div>
        <a class="logo" href="{{ route('home') }}" aria-label="STyli Prates, página inicial" wire:click.prevent="clearFilters"><x-brand /></a>
        <div class="head-icons">
            <button class="icon-btn m-search" type="button" x-on:click="mobileSearch = !mobileSearch; if (mobileSearch) $nextTick(() => $refs.q2.focus())" aria-label="Buscar"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg></button>
            <button class="icon-btn" type="button" aria-label="Favoritos" x-on:click="$store.favs.count ? ($wire.showFavorites($store.favs.ids).then(() => {{ $scrollToShop }})) : $store.ui.toast('Toque no coração das peças para salvar')"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg><span class="badge-count" x-show="$store.favs.count" x-text="$store.favs.count" x-cloak hidden>0</span></button>
            <button class="icon-btn" type="button" aria-label="Abrir sacola" id="bagBtn" :class="{ bump: $store.bag.bumping }" x-on:click="$store.ui.openBag()"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1 12H6zM9 8V6a3 3 0 0 1 6 0v2"/></svg><span class="badge-count" x-show="$store.bag.count" x-text="$store.bag.count" x-cloak hidden>0</span></button>
        </div>
        <div class="m-search-row" id="mSearchRow" x-show="mobileSearch" x-cloak hidden style="grid-column:1 / -1"><div class="search" style="display:block;max-width:none"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg><label class="sr" for="q2">Buscar peças</label><input id="q2" x-ref="q2" type="search" placeholder="Buscar peças" wire:model.live.debounce.250ms="q" x-on:keydown.enter="{{ $scrollToShop }}"></div></div>
    </div>
    <nav class="cats" aria-label="Categorias"><div class="wrap"><ul id="catNav">
        <li><button type="button" wire:click="selectCategory('all')" x-on:click="{{ $scrollToShop }}" aria-pressed="{{ $category === 'all' && ! $q && ! $showingFavorites ? 'true' : 'false' }}">Todas</button></li>
        @foreach ($categories as $c)
            <li><button type="button" wire:click="selectCategory('{{ $c->id }}')" x-on:click="{{ $scrollToShop }}" aria-pressed="{{ $category === (string) $c->id && ! $q && ! $showingFavorites ? 'true' : 'false' }}">{{ $c->name }}</button></li>
        @endforeach
        <li><button type="button" class="sale" wire:click="selectCategory('sale')" x-on:click="{{ $scrollToShop }}" aria-pressed="{{ $category === 'sale' && ! $q ? 'true' : 'false' }}">Promoções</button></li>
    </ul></div></nav>
</header>

<main>
    <section class="hero {{ file_exists(public_path('images/hero.webp')) ? 'hero--photo' : '' }}" aria-label="Nova coleção">
        <div class="wrap">
            <div class="hero-copy">
                <span class="eyebrow">Nova coleção · Brilho do Norte</span>
                <h1>Peças para <i>brilhar</i> todos os dias</h1>
                <p>Semijoias delicadas para usar todo dia e para presentear quem você ama.</p>
                <div class="acts">
                    <button class="btn btn-dark" type="button" wire:click="selectCategory('all')" x-on:click="{{ $scrollToShop }}">Ver coleção</button>
                    @if ($conjuntos)<button class="btn btn-line" type="button" wire:click="selectCategory('{{ $conjuntos->id }}')" x-on:click="{{ $scrollToShop }}">{{ $conjuntos->name }}</button>@endif
                </div>
                <p class="hero-help">Ficou em dúvida? <button type="button" x-on:click="$store.ui.openCare()">Fale com a gente</button>, quem responde é gente de verdade.</p>
            </div>
            <div class="hero-art" aria-hidden="true"><div class="disc"></div><svg viewBox="0 0 200 200">@include('partials.art.hero')</svg></div>
        </div>
    </section>
    @if ($categories->isNotEmpty())
    <section class="block wrap" aria-labelledby="catH">
        <div class="sec-head" data-reveal><h2 id="catH">Compre por <i>categoria</i></h2></div>
        <div class="circles" id="circles" data-reveal>
            @foreach ($categories as $c)
                <button class="circle" type="button" wire:click="selectCategory('{{ $c->id }}')" x-on:click="{{ $scrollToShop }}">
                    <span class="ring">@if ($categoryCovers[$c->id])<img src="{{ $categoryCovers[$c->id] }}" alt="" loading="lazy">@else<x-art :name="\App\Support\CategoryArt::key($c->name)" />@endif</span>{{ $c->name }}
                </button>
            @endforeach
        </div>
    </section>
    @endif

    <section class="block wrap" id="shop" aria-labelledby="shopH">
        <div class="sec-head" data-reveal>
            <div><span class="eyebrow" id="shopEyebrow">{{ $shopEyebrow }}</span><h2 id="shopH">{{ $shopTitle }}</h2></div>
            @if ($filtered)<button class="link-more" type="button" wire:click="clearFilters" id="showAll">Ver todas as peças</button>@endif
        </div>
        <div class="grid" id="grid">
            @forelse ($shopCards as $i => $card)<x-store.card :card="$card" :index="$i" />@empty
                <div class="empty-grid">
                    @if ($showingFavorites)Você ainda não salvou nenhuma peça.@else Nenhuma peça encontrada. Fale com a gente no WhatsApp que a gente procura para você.@endif
                    <br><a class="btn btn-wa" style="margin-top:12px" href="{{ $links->whatsapp($messages->helpInvite()) }}" target="_blank" rel="noopener"><x-store.wa-icon />Falar com a gente</a>
                </div>
            @endforelse
        </div>
    </section>

    @if ($settings->hasBanner())
    @php
        $link = $settings->banner_link ?: 'all';
        $linkCategory = ctype_digit((string) $link) ? $categories->firstWhere('id', (int) $link) : null;
        $link = ctype_digit((string) $link) && ! $linkCategory ? 'all' : $link;
        $bannerPhoto = $settings->bannerPhotoUrl();
    @endphp
    @php $imageOnly = blank($settings->banner_title) && $bannerPhoto; @endphp
    <section class="block wrap" aria-label="{{ $settings->banner_title ?: 'Destaque STyli Prates' }}">
        <button class="feature {{ $bannerPhoto ? 'has-photo' : '' }} {{ $imageOnly ? 'feature--image' : '' }}" type="button" data-reveal
            @if ($link === 'gift') x-on:click="document.getElementById('gift')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
            @else wire:click="selectCategory('{{ $link }}')" x-on:click="{{ $scrollToShop }}" @endif>
            @if ($bannerPhoto)<img class="feature-photo" src="{{ $bannerPhoto }}" alt="{{ $imageOnly ? ($settings->banner_text ?: 'STyli Prates') : '' }}" loading="lazy">@endif
            @unless ($imageOnly)
            <span class="feature-copy">
                @if ($settings->banner_eyebrow)<span class="eyebrow">{{ $settings->banner_eyebrow }}</span>@endif
                <h3>{{ $settings->banner_title }}</h3>
                @if ($settings->banner_text)<p>{{ $settings->banner_text }}</p>@endif
                <span class="btn">{{ $settings->banner_button ?: 'Ver peças' }}</span>
            </span>
            @endunless
        </button>
    </section>
    @endif

    @if ($newCards->isNotEmpty())
    <section class="block wrap" aria-labelledby="newH">
        <div class="sec-head" data-reveal><div><span class="eyebrow">Acabaram de chegar</span><h2 id="newH"><i>Lançamentos</i></h2></div></div>
        <div class="grid" id="gridNew">@foreach ($newCards as $i => $card)<x-store.card :card="$card" :index="$i" />@endforeach</div>
    </section>
    @endif

    <section class="block wrap how" id="how" aria-labelledby="howH">
        <div class="sec-head" data-reveal><div><span class="eyebrow">Simples e sem pressa</span><h2 id="howH">Como funciona comprar <i>com a gente</i></h2></div></div>
        <div class="steps">
            <div class="step" data-reveal><span class="n">1</span><div><h3>Escolha com calma</h3><p>Monte sua sacola aqui no site. Nada é cobrado agora.</p></div></div>
            <div class="step" data-reveal style="--i:1"><span class="n">2</span><div><h3>Converse com a gente</h3><p>Sua sacola chega no nosso WhatsApp. A gente confirma as peças e tira todas as suas dúvidas.</p></div></div>
            <div class="step" data-reveal style="--i:2"><span class="n">3</span><div><h3>Receba ou retire</h3><p>Entregamos em {{ $settings->city ?: 'nossa cidade' }} ou você retira na loja. Para outras cidades, a gente combina o envio e o frete com você. Pague no Pix, cartão ou dinheiro.</p></div></div>
        </div>
        <p class="how-note" data-reveal>Prefere ir direto? <b>Fale com a gente</b> e a gente separa para você.</p>
    </section>

    <section class="gift" id="gift" aria-labelledby="giftH">
        <div class="wrap">
            <div data-reveal><span class="eyebrow">Ajuda para presente</span><h2 id="giftH">Não sabe o que dar? <i>A gente ajuda.</i></h2><p class="lead">Conte para quem é e quanto quer gastar. A gente responde no WhatsApp com sugestões e fotos das peças.</p></div>
            <form class="gift-form" id="giftForm" method="GET" action="{{ route('storefront.gift') }}" target="_blank" data-reveal style="--i:1">
                <fieldset class="gq"><legend>Para quem é?</legend><div class="chips">
                    @foreach (\App\Http\Controllers\Storefront\GiftController::RECIPIENTS as $r)<label class="chip"><input type="radio" name="para" value="{{ $r }}" @checked($loop->first)><span>{{ $r }}</span></label>@endforeach
                </div></fieldset>
                <label class="gq" for="gOcc"><span>Qual a ocasião?</span><select id="gOcc" name="ocasiao">@foreach (\App\Http\Controllers\Storefront\GiftController::OCCASIONS as $o)<option>{{ $o }}</option>@endforeach</select></label>
                <fieldset class="gq"><legend>Quanto quer gastar?</legend><div class="chips">
                    @foreach (\App\Http\Controllers\Storefront\GiftController::BUDGETS as $b)<label class="chip"><input type="radio" name="valor" value="{{ $b }}" @checked($loop->index === 1)><span>{{ $b }}</span></label>@endforeach
                </div></fieldset>
                <label class="gq" for="gNote"><span>Algum detalhe? <small>(opcional)</small></span><input class="txt" id="gNote" name="detalhe" maxlength="140" placeholder="Ex.: ela gosta de dourado, usa brinco pequeno"></label>
                <button class="btn btn-wa" type="submit"><x-store.wa-icon />Pedir sugestões no WhatsApp</button>
            </form>
        </div>
    </section>

    <section class="benefits" aria-label="Vantagens">
        <div class="wrap">
            <div class="benefit" data-reveal><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h12l3 6-9 12L3 9z"/><path d="M3 9h18M9 3l3 18 3-18"/></svg><div><b>Antialérgicas</b><span>Livres de níquel, para usar todo dia</span></div></div>
            <div class="benefit" data-reveal><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></svg><div><b>{{ $warranty ? 'Garantia de '.$warranty : 'Garantia no banho' }}</b><span>No banho das peças</span></div></div>
            <div class="benefit" data-reveal><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/></svg><div><b>Entrega em {{ $settings->city ?: 'nossa cidade' }}</b><span>Outras cidades: frete a combinar</span></div></div>
            <div class="benefit" data-reveal><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/></svg><div><b>{{ $pix > 0 ? $pix.'% off no Pix' : 'Pix e cartão' }}</b><span>Ou em até {{ $inst }}x sem juros</span></div></div>
        </div>
    </section>

    <section class="care-banner" aria-labelledby="realH">
        <h2 id="realH" class="sr-only">Atendimento de verdade: aqui quem te responde é gente, não robô.</h2>
        <button type="button" class="feature feature--image" data-reveal x-on:click="$store.ui.openCare()" aria-label="Falar com a gente no WhatsApp">
            <img class="feature-photo" src="{{ asset('images/atendimento.webp') }}" alt="Atendimento de verdade: aqui quem te responde é gente, não robô. Fale com a gente!" loading="lazy" width="2000" height="750">
        </button>
        @if ($addressLine)<p class="care-addr">{{ $addressLine }}</p>@endif
    </section>

    @if ($instagram)
    <section class="block wrap" aria-labelledby="igH">
        <div class="sec-head" data-reveal><div><span class="eyebrow">Siga a gente</span><h2 id="igH">{{ '@'.ltrim($instagram, '@') }}</h2></div><a href="{{ $instagramUrl }}" target="_blank" rel="noopener">Ver Instagram</a></div>
        <div class="insta" id="insta">
            @php $tones = ['#FFF1EC', '#E8C9CF', '#F3E9E2', '#F4DDE0', '#EBD3D3', '#FBEFE8']; $arts = [['point', 'gold'], ['tennis', 'silver'], ['drop', 'gold'], ['band', 'gold'], ['heart', 'gold'], ['cuff', 'silver']]; @endphp
            @foreach (range(0, 5) as $i)
                @php $tile = $instagramTiles[$i] ?? null; @endphp
                <div data-reveal style="--i:{{ $i }};background:{{ $tones[$i] }}">
                    @if ($tile?->coverUrl)<img src="{{ $tile->coverUrl }}" alt="" loading="lazy">@else<x-art :name="$arts[$i][0]" :metal="$arts[$i][1]" />@endif
                </div>
            @endforeach
        </div>
    </section>
    @endif

    <section class="block wrap care-tips" aria-labelledby="ctH">
        <div class="ct-head" data-reveal><div><span class="eyebrow">Para durar muito</span><h2 id="ctH">Cuidados com <i>suas peças</i></h2><p>Com alguns cuidados simples, sua semijoia mantém o brilho por muito mais tempo.</p></div><button class="link-more" type="button" x-on:click="$store.ui.openGuide()">Ver o guia completo</button></div>
        <div class="ct-grid">
            @foreach ([
                ['use', 'Última a colocar', 'Coloque depois do perfume e do creme, e tire antes de dormir.', '<path d="M12 3v4M12 17v4M3 12h4M17 12h4"/><circle cx="12" cy="12" r="3"/>'],
                ['avoid', 'Longe da água', 'Tire para tomar banho, ir à piscina, ao mar ou à academia.', '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"/><path d="M4 4l16 16"/>'],
                ['clean', 'Limpe com flanela', 'Depois de usar, passe uma flanela macia e seca.', '<path d="M5 19c2-6 4-9 7-9s5 3 7 9"/><path d="M8 7c1-2 2.5-3 4-3s3 1 4 3"/>'],
                ['keep', 'Guarde separada', 'Cada peça no seu saquinho, em lugar seco.', '<rect x="4" y="8" width="16" height="12" rx="1"/><path d="M4 12h16M9 8V5h6v3"/>'],
            ] as $i => [$key, $title, $text, $icon])
                <button class="ct" type="button" x-on:click="$store.ui.openGuide('{{ $key }}')" data-reveal style="--i:{{ $i }}"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon !!}</svg><b>{{ $title }}</b><span>{{ $text }}</span></button>
            @endforeach
        </div>
    </section>
</main>

<footer>
    <div class="wrap cols">
        <div><a class="logo" href="{{ route('home') }}" style="align-items:flex-start"><x-brand class="brand-logo footer-logo" /></a><p style="color:var(--muted);margin:14px 0 0;max-width:34ch">Semijoias para o dia a dia e ocasiões especiais{{ $settings->city ? ', em '.$settings->city.($settings->state ? ', '.$settings->state : '') : '' }}.</p></div>
        <div><h4>Categorias</h4><ul id="footCats">@foreach ($categories as $c)<li><button type="button" class="human" style="background:none;border:0;padding:0;font:inherit;color:inherit;font-weight:400;cursor:pointer" wire:click="selectCategory('{{ $c->id }}')" x-on:click="{{ $scrollToShop }}">{{ $c->name }}</button></li>@endforeach</ul></div>
        <div><h4>Ajuda</h4><ul>
            <li><a href="#how">Como comprar</a></li><li><a href="#gift">Ajuda para presente</a></li>
            <li><a href="#" x-on:click.prevent="$store.ui.openGuide('warranty')">Trocas e garantia</a></li>
            <li><a href="#" x-on:click.prevent="$store.ui.openGuide()">Cuidados com as peças</a></li>
        </ul></div>
        <div><h4>Atendimento</h4><ul>
            <li class="human">Por gente de verdade, não robô</li>
            @if ($settings->whatsapp)<li>WhatsApp {{ \App\Support\Phone::format($settings->whatsapp) }}</li>@endif
            @if ($settings->hours_weekdays)<li>Seg a sex · {{ $settings->hours_weekdays }}</li>@endif
            @if ($settings->hours_saturday)<li>Sábado · {{ $settings->hours_saturday }}</li>@endif
            @if ($settings->hours_sunday)<li>Domingo · {{ $settings->hours_sunday }}</li>@endif
            @if ($addressLine)<li>{{ $addressLine }}</li>@endif
        </ul></div>
    </div>
    <div class="wrap bottom"><span>© {{ now()->year }} STyli Prates{{ $settings->cnpj ? ' · CNPJ '.$settings->cnpj : '' }}</span><span class="pay"><span>PIX</span><span>VISA</span><span>MASTER</span><span>ELO</span></span></div>
</footer>
</div>
