@php use App\Support\Money; @endphp
<div>
    <div class="page-head">
        <div>
            <a class="back-link" href="{{ route('admin.sales') }}"><x-icon name="left" />Vendas</a>
            <h1>Nova venda</h1>
            <p>Toque nas peças vendidas, escolha a cliente e o pagamento.</p>
        </div>
    </div>

    <div class="pos">
        <section class="card panel">
            <div class="field-inline"><x-icon name="search" /><input class="input" type="search" wire:model.live.debounce.250ms="productSearch" placeholder="Buscar peça" aria-label="Buscar peça"></div>
            <div class="pgrid">
                @forelse ($products as $product)
                    @php $inCart = $cart[$product->id] ?? 0; @endphp
                    <button class="pitem {{ $inCart ? 'on' : '' }}" type="button" wire:click="add({{ $product->id }})" wire:key="p-{{ $product->id }}" aria-label="Adicionar {{ $product->name }}">
                        <span class="pthumb">
                            @if ($cover = $product->cover())<img src="{{ $cover->getUrl('thumb') }}" alt="">@endif
                            @if ($inCart)<span class="qbadge">{{ $inCart }}</span>@endif
                        </span>
                        <span class="pname">{{ $product->name }}</span>
                        <span class="pprice">{{ Money::from($product->price)->format() }}</span>
                    </button>
                @empty
                    <div class="empty">Nenhuma peça encontrada.</div>
                @endforelse
            </div>
        </section>

        <section class="card panel cart" id="cart">
            <h2>Venda atual</h2>

            @if ($lines->isNotEmpty())
                <div class="clines">
                    @foreach ($lines as $line)
                        <div class="cline" wire:key="line-{{ $line['product']->id }}">
                            <span class="cthumb">@if ($cover = $line['product']->cover())<img src="{{ $cover->getUrl('thumb') }}" alt="">@endif</span>
                            <span class="cname">{{ $line['product']->name }}<small>{{ Money::from($line['product']->price)->format() }} cada</small></span>
                            <span class="qty">
                                <button class="icon-btn" type="button" wire:click="changeQuantity({{ $line['product']->id }}, -1)" aria-label="Diminuir">−</button>
                                <b>{{ $line['quantity'] }}</b>
                                <button class="icon-btn" type="button" wire:click="changeQuantity({{ $line['product']->id }}, 1)" aria-label="Aumentar">+</button>
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="hint">Toque nas peças ao lado para adicionar.</p>
            @endif
            @error('items')<span class="err">{{ $message }}</span>@enderror

            <div class="fgroup">
                <span class="flabel">Cliente @unless ($method?->generates_receivables)<small>(opcional)</small>@endunless</span>
                @if ($customer)
                    <div class="cchip">
                        <span class="avatar sm">{{ $customer->initials() }}</span>
                        <span class="cchip-t"><b>{{ $customer->name }}</b><small>{{ $customer->whatsapp ? $customer->whatsappFormatted() : 'Sem telefone' }}</small></span>
                        <button class="icon-btn" type="button" wire:click="clearCustomer" aria-label="Trocar cliente"><x-icon name="x" /></button>
                    </div>
                @else
                    <div class="combo" x-data x-on:focus-customer.window="$refs.search.focus()" x-on:click.outside="$wire.closeCombo()">
                        <span class="combo-ico"><x-icon name="search" /></span>
                        <input class="input" id="cSearch" x-ref="search" wire:model.live.debounce.200ms="customerQuery" wire:focus="openCombo" placeholder="Digite o nome ou telefone" autocomplete="off" role="combobox" aria-expanded="{{ $comboOpen ? 'true' : 'false' }}" aria-controls="cList" aria-autocomplete="list" aria-label="Buscar cliente">
                        @if ($comboOpen)
                            <div class="combo-list" id="cList" role="listbox">
                                @foreach ($matches as $i => $match)
                                    <button type="button" class="combo-opt {{ $i === 0 ? 'active' : '' }}" role="option" wire:click="pickCustomer({{ $match->id }})" wire:key="m-{{ $match->id }}">
                                        <span class="avatar sm">{{ $match->initials() }}</span>
                                        <span><b>{{ $match->name }}</b><small>{{ $match->whatsapp ? $match->whatsappFormatted() : 'Sem telefone' }}</small></span>
                                    </button>
                                @endforeach
                                <button type="button" class="combo-opt new {{ $matches->isEmpty() ? 'active' : '' }}" role="option" wire:click="openQuick">
                                    <x-icon name="plus" />{{ trim($customerQuery) !== '' && ! preg_match('/\d/', $customerQuery) ? 'Cadastrar “'.trim($customerQuery).'”' : 'Cadastrar nova cliente' }}
                                </button>
                            </div>
                        @endif
                    </div>
                    <button class="link-btn add-client" type="button" wire:click="openQuick"><x-icon name="plus" />Cadastrar nova cliente</button>
                @endif
                @error('customer')<span class="err">{{ $message }}</span>@enderror
            </div>

            <div class="fgroup">
                <span class="flabel">Pagamento</span>
                <div class="chips-choose">
                    @foreach ($methods as $m)
                        <label class="chip-opt"><input type="radio" wire:model.live="payment" value="{{ $m->slug }}">{{ $m->name }}</label>
                    @endforeach
                </div>
                @error('payment')<span class="err">{{ $message }}</span>@enderror
            </div>

            @if ($method?->allows_installments)
                <div class="fgroup">
                    <span class="flabel">Parcelas</span>
                    <div class="chips-choose">
                        @for ($n = 1; $n <= $maxInstallments; $n++)
                            <label class="chip-opt"><input type="radio" wire:model.live="installments" value="{{ $n }}">{{ $n === 1 ? ($payment === 'credito' ? 'À vista' : '1x') : $n.'x' }}</label>
                        @endfor
                    </div>
                    @error('installments')<span class="err">{{ $message }}</span>@enderror
                </div>
            @endif

            @if ($method?->generates_receivables)
                <div class="crediario-box">
                    <label class="f" for="sDue">1º vencimento<input class="input" id="sDue" type="date" wire:model.live="firstDue"></label>
                    <p class="hint">Crediário da loja: as parcelas ficam em aberto na ficha da cliente até você marcar como recebidas.</p>
                </div>
            @endif

            <div class="fgroup">
                <span class="flabel">Onde vendeu</span>
                <div class="chips-choose">
                    @foreach ($channels as $c)
                        <label class="chip-opt"><input type="radio" wire:model="channel" value="{{ $c->slug }}">{{ $c->name }}</label>
                    @endforeach
                </div>
                @error('channel')<span class="err">{{ $message }}</span>@enderror
            </div>

            <div class="fields">
                <label class="f" for="sDate">Data da venda<input class="input" id="sDate" type="date" wire:model="date" max="{{ today()->toDateString() }}">@error('date')<span class="err">{{ $message }}</span>@enderror</label>
                <label class="f" for="sExtra">Outro desconto<span class="money"><span>R$</span><input class="input" id="sExtra" inputmode="decimal" placeholder="0,00" wire:model.live.debounce.300ms="extraDiscount"></span></label>
            </div>

            <div class="sum" id="cartSum">
                <div class="sum-row"><span>Subtotal</span><span>{{ $subtotal->format() }}</span></div>
                @if ($totals && ! $totals['pix_discount']->isZero())
                    <div class="sum-row ok"><span>Desconto Pix ({{ $pixPercent }}%)</span><span>− {{ $totals['pix_discount']->format() }}</span></div>
                @endif
                @if ($totals && ! $totals['other_discount']->isZero())
                    <div class="sum-row ok"><span>Outro desconto</span><span>− {{ $totals['other_discount']->format() }}</span></div>
                @endif
                <div class="sum-row total"><span>Total</span><b>{{ ($totals['total'] ?? Money::zero())->format() }}</b></div>
                @php $parceled = $method?->allows_installments && $installments > 1; @endphp
                <div class="sum-row"><span>{{ $parceled ? 'Parcelado' : 'À vista' }}</span><span>{{ $parceled ? $installments.'x de '.($totals['total'] ?? Money::zero())->divideRound($installments)->format() : $method?->name }}</span></div>
            </div>

            <div class="cart-acts">
                <button class="btn btn-ghost" type="button" wire:click="clearCart" @disabled($lines->isEmpty())>Limpar</button>
                <button class="btn btn-primary" type="button" wire:click="register" wire:loading.attr="disabled" @disabled($lines->isEmpty())><x-icon name="check" />Registrar venda</button>
            </div>
        </section>
    </div>

    @if ($count)
        <button class="cart-fab" type="button" x-data x-on:click="document.getElementById('cart').scrollIntoView({behavior:'smooth'})">{{ $count }} {{ $count === 1 ? 'peça' : 'peças' }} · {{ ($totals['total'] ?? Money::zero())->format() }}<span>Ver venda</span></button>
    @endif

    @if ($showQuick)
        <livewire:admin.customers.quick-create :initial-name="$customerQuery" wire:key="quick-create" />
    @endif
</div>
