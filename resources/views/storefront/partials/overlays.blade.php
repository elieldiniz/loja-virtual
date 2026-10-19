@php
    $photoUrl = $store->chatButtonPhotoUrl();
    $waIcon = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-12.5 7.4L3 21l2.1-5.4A8.4 8.4 0 1 1 21 11.5z"/></svg>';
    $config = [
        'helpInvite' => ['enabled' => (bool) $store->help_invite_enabled, 'minutes' => (int) $store->help_invite_minutes],
    ];
    $chev = '<svg class="go" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>';
@endphp

<div x-data="care(@js($config))" x-on:keydown.escape.window="close()" x-on:click.outside="if ($store.ui.careOpen) $store.ui.careOpen = false">
    <button class="care-fab" type="button" id="careFab" aria-label="Falar com o atendimento" :aria-expanded="$store.ui.careOpen ? 'true' : 'false'" aria-controls="carePanel" aria-expanded="false" x-on:click="$store.ui.careOpen ? ($store.ui.careOpen = false) : $store.ui.openCare()">
        @if ($photoUrl)<img src="{{ $photoUrl }}" alt="">@else<x-monogram variant="light" />@endif
        <span class="wa-mini">{!! $waIcon !!}</span>
    </button>

    <div class="care-bubble" id="careBubble" x-show="$store.ui.bubbleOn" x-cloak hidden x-on:click="$store.ui.openCare()">
        <button class="x" type="button" aria-label="Fechar" x-on:click.stop="$store.ui.hideBubble()">×</button>
        <b>{{ $welcome }}</b>
    </div>

    <div class="care-panel" :class="{ on: $store.ui.careOpen }" id="carePanel" role="dialog" aria-modal="false" aria-labelledby="careH" :inert="!$store.ui.careOpen">
        <div class="care-head">
            <span class="mono"><x-monogram /></span>
            <div><b id="careH">Atendimento STyli Prates</b><small>A gente responde rapidinho pelo WhatsApp</small></div>
            <button class="icon-btn" type="button" aria-label="Fechar atendimento" x-on:click="$store.ui.careOpen = false"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
        </div>
        <div class="care-body">
            <div class="care-msg" id="careWelcome">{{ $welcome }}</div>
            <div id="careOpts" style="display:flex;flex-direction:column;gap:8px">
                @foreach ($shortcuts as $shortcut)
                    @if ($shortcut['gift'])
                        <button class="care-opt" type="button" x-on:click="$store.ui.careOpen = false; document.getElementById('gift')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 11h16v9H4zM3 7h18v4H3zM12 7v13M12 7c-2-4-6-3-5 0M12 7c2-4 6-3 5 0"/></svg><span>{{ $shortcut['label'] }}</span>{!! $chev !!}
                        </button>
                    @else
                        <a class="care-opt" href="{{ $shortcut['url'] }}" target="_blank" rel="noopener">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-12.5 7.4L3 21l2.1-5.4A8.4 8.4 0 1 1 21 11.5z"/></svg><span>{{ $shortcut['label'] }}</span>{!! $chev !!}
                        </a>
                    @endif
                @endforeach
                @if (empty($shortcuts))
                    <a class="care-opt" href="{{ $links->whatsapp('Oi! Tenho uma dúvida:') }}" target="_blank" rel="noopener"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-12.5 7.4L3 21l2.1-5.4A8.4 8.4 0 1 1 21 11.5z"/></svg><span>Falar com a gente</span>{!! $chev !!}</a>
                @endif
            </div>
            <p class="care-foot" id="careFoot">Abre o WhatsApp com a mensagem pronta.@if ($hoursLine) {{ $hoursLine }}@endif</p>
        </div>
    </div>

    {{-- convite de ajuda: cliente em dúvida, sacola vazia --}}
    <div class="help-bg" :class="{ on: $store.ui.helpOpen }" id="helpBg" :aria-hidden="$store.ui.helpOpen ? 'false' : 'true'" aria-hidden="true" x-on:click.self="$store.ui.helpOpen = false" x-on:keydown.escape.window="$store.ui.helpOpen = false">
        <div class="help" id="helpBox" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="helpH" x-ref="helpBox">
            <button class="icon-btn x" type="button" aria-label="Fechar" x-on:click="$store.ui.helpOpen = false"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
            <div class="help-art" aria-hidden="true"><div class="disc"></div><svg class="main" viewBox="0 0 200 200">@include('partials.art.help')</svg><span class="talk">Oi! 💛</span></div>
            <div class="help-copy">
                <span class="eyebrow">Uma ajudinha?</span>
                <h2 id="helpH">Ficou na dúvida? <i>A gente te ajuda</i> a escolher.</h2>
                <p>São tantas peças lindas que às vezes fica difícil decidir. Conta pra nossa atendente o que você procura, seu estilo ou para quem é, e ela separa as opções certinhas pra você.</p>
                <p><b style="color:var(--ink);font-weight:500">Vai ser um prazer te atender.</b></p>
                <div class="acts">
                    <a class="btn btn-wa" href="{{ $links->whatsapp($messages->helpInvite()) }}" target="_blank" rel="noopener" x-on:click="$store.ui.helpOpen = false"><x-store.wa-icon />Falar com a atendente</a>
                    <button class="btn btn-line" type="button" x-on:click="$store.ui.helpOpen = false; document.getElementById('gift')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">É para presente</button>
                    <button class="later" type="button" x-on:click="$store.ui.helpOpen = false; $store.ui.toast('Tudo bem! Se precisar, é só tocar no botão da STyli Prates')">Agora não, obrigada</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="scrim" :class="{ on: $store.ui.scrim }" id="scrim" x-data x-on:click="$store.ui.closeAll()"></div>

{{-- sacola --}}
<aside class="drawer" :class="{ on: $store.ui.bagOpen }" id="drawer" aria-label="Sacola" :aria-hidden="$store.ui.bagOpen ? 'false' : 'true'" aria-hidden="true" :inert="!$store.ui.bagOpen" x-data x-on:keydown.escape.window="$store.ui.bagOpen = false">
    <div class="drawer-head"><h2>Sua sacola</h2><button class="icon-btn" type="button" aria-label="Fechar sacola" x-on:click="$store.ui.bagOpen = false"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button></div>
    <div class="bag-list" id="bagList">
        <template x-if="!$store.bag.count">
            <div class="bag-empty"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1 12H6zM9 8V6a3 3 0 0 1 6 0v2"/></svg><p>Sua sacola está vazia.</p><button class="btn btn-line" type="button" x-on:click="$store.ui.closeAll(); document.getElementById('shop')?.scrollIntoView({ behavior: 'smooth' })">Ver peças</button></div>
        </template>
        <template x-for="(line, i) in ($store.bag.summary?.lines ?? [])" :key="line.id">
            <div class="bag-item" :style="`--i:${i}`">
                <div class="th"><template x-if="line.thumb"><img :src="line.thumb" alt=""></template></div>
                <div>
                    <h4 x-text="line.name"></h4>
                    <div class="qty"><button type="button" aria-label="Diminuir quantidade" x-on:click="$store.bag.dec(line.id)">−</button><span x-text="line.quantity"></span><button type="button" aria-label="Aumentar quantidade" x-on:click="$store.bag.inc(line.id)">+</button></div><br>
                    <button class="rm" type="button" x-on:click="$store.bag.remove(line.id)">Remover</button>
                </div>
                <strong style="font-variant-numeric:tabular-nums" x-text="line.total"></strong>
            </div>
        </template>
    </div>
    <div class="bag-foot" id="bagFoot" x-show="$store.bag.count && $store.bag.summary" x-cloak>
        <div class="row"><span>Subtotal</span><span x-text="$store.bag.summary?.subtotal"></span></div>
        <div class="row" style="color:var(--wa)" x-show="$store.bag.summary?.pix_percent > 0"><span x-text="`No Pix (${$store.bag.summary?.pix_percent}% off)`"></span><span x-text="$store.bag.summary?.pix_total"></span></div>
        <div class="row total"><span>Total</span><span x-text="$store.bag.summary?.subtotal"></span></div>
        <small x-text="`ou ${$store.bag.summary?.installments}x de ${$store.bag.summary?.installment_amount} sem juros`"></small>
        <div class="bag-note"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-12.5 7.4L3 21l2.1-5.4A8.4 8.4 0 1 1 21 11.5z"/></svg><span>A gente confirma se tem as peças e combina entrega e pagamento. <b>Nada é cobrado agora.</b></span></div>
        <p style="margin:0;font-size:12px;color:var(--muted)">Entrega em {{ $store->city ?: 'nossa cidade' }} ou retirada na loja. Para outras cidades, envio e frete combinados na conversa. Pagamento no Pix, cartão ou dinheiro.</p>
        <a class="btn btn-wa" :href="$store.bag.summary?.whatsapp_url ?? '#'" target="_blank" rel="noopener"><x-store.wa-icon />Enviar para o atendimento</a>
    </div>
</aside>

{{-- página da peça --}}
<div class="modal" :class="{ on: $store.ui.productId !== null }" id="modal" role="dialog" aria-modal="true" aria-labelledby="mTitle" x-data="productModal()" x-effect="load($store.ui.productId)" x-on:keydown.escape.window="$store.ui.productId = null" x-on:click.self="$store.ui.productId = null">
    <div class="modal-card">
        <button class="icon-btn m-close" type="button" id="mClose" aria-label="Fechar" x-on:click="$store.ui.productId = null"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
        <div class="m-gallery">
            <div class="m-main" id="mMain"><template x-if="product && product.photos[view]"><img :src="product.photos[view].url" :alt="product.name"></template></div>
            <div class="m-thumbs" id="mThumbs">
                <template x-for="(photo, i) in (product?.photos ?? [])" :key="i">
                    <button type="button" :aria-label="`Foto ${i + 1}`" :aria-pressed="view === i ? 'true' : 'false'" x-on:click="view = i"><img :src="photo.thumb" alt=""></button>
                </template>
            </div>
        </div>
        <div class="m-info" id="mInfo">
            <template x-if="loading"><p>Carregando…</p></template>
            <template x-if="error"><p>Não foi possível carregar a peça. Tente novamente.</p></template>
            <template x-if="product">
                <div style="display:contents">
                    <span class="eyebrow" x-text="product.category"></span>
                    <h2 id="mTitle" x-text="product.name"></h2>
                    <div class="prices"><template x-if="product.old_price"><s x-text="product.old_price"></s></template><strong x-text="product.price"></strong></div>
                    <span class="inst" x-text="`ou ${product.installments}x de ${product.installment_amount} sem juros`"></span>
                    <span class="pix" x-text="`${product.pix_price} no Pix`"></span>
                    <p x-show="product.description" x-text="product.description" style="margin:0;color:var(--muted)"></p>
                    <ul>
                        <li x-text="product.is_solid_silver ? 'Banho: prata 925 maciça' : 'Banho: folheado a ouro 18k'"></li>
                        <li>Antialérgico, livre de níquel</li>
                        <li x-text="product.warranty ? `Garantia de ${product.warranty} no banho` : 'Garantia no banho'"></li>
                        <li>Acompanha caixinha para presente</li>
                    </ul>
                    <button class="btn btn-dark" type="button" x-on:click="$store.bag.add(product.id, product.name); if (matchMedia('(max-width: 720px)').matches) $store.ui.closeAll()">Quero essa</button>
                    <a class="btn btn-wa" :href="product.question_url" target="_blank" rel="noopener"><x-store.wa-icon />Tirar dúvida sobre esta peça</a>
                </div>
            </template>
        </div>
    </div>
</div>

{{-- guia de cuidados --}}
<div class="guide-bg" :class="{ on: $store.ui.guideOpen }" id="guideBg" :aria-hidden="$store.ui.guideOpen ? 'false' : 'true'" aria-hidden="true" x-data="guide()" x-effect="focusSection($store.ui.guideOpen, $store.ui.guideSection)" x-on:click.self="$store.ui.closeGuide()" x-on:keydown.escape.window="$store.ui.closeGuide()">
    <article class="guide" id="guide" role="dialog" aria-modal="true" aria-labelledby="guideH" tabindex="-1" x-ref="guide">
        <button class="icon-btn x" type="button" aria-label="Fechar guia" x-on:click="$store.ui.closeGuide()"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
        <header class="guide-hero"><span class="eyebrow">Guia de cuidados</span><h2 id="guideH">Sua semijoia <i>brilhando</i> por muito mais tempo</h2><p>A semijoia tem uma camada de banho sobre o metal. Tudo o que desgasta essa camada (atrito, umidade e produtos químicos) encurta a vida da peça. Os cuidados abaixo evitam isso.</p><svg class="deco" viewBox="0 0 200 200" aria-hidden="true">@include('partials.art.guide')</svg></header>
        <div class="guide-body">
            @include('storefront.partials.guide-sections', ['warranty' => $store->plating_warranty])
            <div class="guide-cta"><div><b>Ficou com alguma dúvida?</b><span>Manda uma foto da sua peça que a gente te ajuda.</span></div><a class="btn btn-wa" href="{{ $links->whatsapp($messages->careQuestion()) }}" target="_blank" rel="noopener"><x-store.wa-icon />Falar com a gente</a></div>
        </div>
    </article>
</div>

<div class="toast" :class="{ on: $store.ui.toastOn }" id="toast" role="status" aria-live="polite" x-data x-text="$store.ui.toastMsg"></div>
