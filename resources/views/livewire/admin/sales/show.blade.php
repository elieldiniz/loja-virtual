@php
    use App\Services\SaleNumberGenerator;
    use App\Support\Format;
    use App\Support\Money;
@endphp
<div>
    <div class="page-head">
        <div>
            <a class="back-link" href="{{ route('admin.sales') }}"><x-icon name="left" />Vendas</a>
            <h1>Venda {{ SaleNumberGenerator::format($sale->number) }}@if ($sale->isCancelled()) <span class="pill canc big">Cancelada</span>@endif</h1>
            <p>{{ Format::dayTitle($sale->sold_at) }} às {{ $sale->sold_at->format('H:i') }} · {{ $sale->channel->name }}</p>
        </div>
        <div class="head-acts">
            @if ($receiptUrl)<a class="btn btn-ghost" href="{{ $receiptUrl }}" target="_blank" rel="noopener"><x-icon name="wa" />Enviar recibo</a>@endif
            @if ($sale->isCancelled())
                <button class="btn btn-ghost" type="button" wire:click="reactivate">Reativar venda</button>
            @else
                <button class="btn btn-danger" type="button" wire:click="askCancel"><x-icon name="x" />Cancelar venda</button>
            @endif
            <a class="btn btn-primary" href="{{ route('admin.sales.create') }}"><x-icon name="plus" />Nova venda</a>
        </div>
    </div>

    @if ($sale->isCancelled())
        <div class="canc-note">
            <x-icon name="alert" />
            <span><b>Cancelada em {{ $sale->cancelled_at->format('d/m/Y') }} às {{ $sale->cancelled_at->format('H:i') }}.</b> Motivo: {{ $sale->cancel_reason }}<br>Esta venda não conta no Painel nem no total da cliente.</span>
        </div>
    @endif

    <div class="client-grid">
        <div class="col">
            <section class="card box">
                <h2>Peças <small class="h-sub">{{ Format::pieces($pieces) }}</small></h2>
                <div class="ilines">
                    @foreach ($sale->items as $item)
                        <div class="iline" wire:key="item-{{ $item->id }}">
                            <span class="cthumb">@if ($cover = $item->product?->cover())<img src="{{ $cover->getUrl('thumb') }}" alt="">@endif</span>
                            <span class="cname">{{ $item->product_name }}<small>{{ $item->material_name }}@if ($item->category_name) · {{ $item->category_name }}@endif</small></span>
                            <span class="iq">{{ $item->quantity }} × {{ Money::from($item->unit_price)->format() }}</span>
                            <b class="it">{{ Money::from($item->line_total)->format() }}</b>
                        </div>
                    @endforeach
                </div>
                <div class="sum">
                    <div class="sum-row"><span>Subtotal</span><span>{{ Money::from($sale->subtotal)->format() }}</span></div>
                    @if ((float) $sale->pix_discount > 0)<div class="sum-row ok"><span>Desconto Pix</span><span>− {{ Money::from($sale->pix_discount)->format() }}</span></div>@endif
                    @if ((float) $sale->other_discount > 0)<div class="sum-row ok"><span>Outro desconto</span><span>− {{ Money::from($sale->other_discount)->format() }}</span></div>@endif
                    <div class="sum-row total"><span>Total</span><b>{{ $total->format() }}</b></div>
                </div>
            </section>

            @if ($sale->installments->isNotEmpty())
                <section class="card box">
                    <h2>Parcelas do crediário</h2>
                    <div class="parcels">
                        @foreach ($sale->installments as $installment)<x-parcel-row :installment="$installment" wire:key="parcel-{{ $installment->id }}" />@endforeach
                    </div>
                </section>
            @endif
        </div>
        <div class="col">
            <section class="card box">
                <h2>Cliente</h2>
                @if ($sale->customer)
                    <div class="cchip plain">
                        <span class="avatar sm">{{ $sale->customer->initials() }}</span>
                        <span class="cchip-t"><b>{{ $sale->customer->name }}</b><small>{{ $sale->customer->whatsapp ? $sale->customer->whatsappFormatted() : 'Sem telefone' }}@if ($sale->customer->city) · {{ $sale->customer->city }}@endif</small></span>
                    </div>
                    <a class="btn btn-ghost" href="{{ route('admin.customers.edit', $sale->customer) }}">Ver ficha da cliente</a>
                @else
                    <p class="hint">Venda sem cliente cadastrada.</p>
                @endif
            </section>
            <section class="card box">
                <h2>Pagamento</h2>
                <div class="kv"><span>Forma</span><span>{{ $sale->paymentMethod->name }}</span></div>
                <div class="kv"><span>Condição</span><span>{{ $sale->installments_count > 1 ? 'Parcelado em '.$sale->installments_count.'x de '.$total->divideRound($sale->installments_count)->format() : 'À vista' }}</span></div>
                @if ($sale->installments->isNotEmpty())
                    <div class="kv"><span>Recebido</span><span>{{ $received->format() }} de {{ $total->format() }}</span></div>
                @endif
            </section>
            <section class="card box">
                <h2>Detalhes</h2>
                <div class="kv"><span>Número</span><span>{{ SaleNumberGenerator::format($sale->number) }}</span></div>
                <div class="kv"><span>Data</span><span>{{ $sale->sold_at->format('d/m/Y') }} às {{ $sale->sold_at->format('H:i') }}</span></div>
                <div class="kv"><span>Onde vendeu</span><span>{{ $sale->channel->name }}</span></div>
            </section>
        </div>
    </div>

    @if ($confirmingCancel)
        <div class="sheet-bg" wire:click.self="closeCancel">
            <div class="sheet" role="alertdialog" aria-modal="true" aria-labelledby="cfH">
                <h3 id="cfH">Cancelar a venda {{ SaleNumberGenerator::format($sale->number) }}?</h3>
                <p>A venda sai do Painel, da ficha da cliente e do crediário a receber, mas continua no histórico. Se foi engano, é possível reativar.@if (! $received->isZero()) <b>Atenção: já foram recebidos {{ $received->format() }} do crediário; combine a devolução com a cliente.</b>@endif</p>
                <label class="f" for="cfReason">Motivo do cancelamento
                    <textarea class="textarea" id="cfReason" wire:model="reason" style="min-height:70px" placeholder="Ex.: cliente desistiu, troca por outra peça, lançada errado"></textarea>
                    @error('reason')<span class="err">{{ $message }}</span>@enderror
                </label>
                <div class="acts">
                    <button class="btn btn-ghost" type="button" wire:click="closeCancel">Voltar</button>
                    <button class="btn btn-primary" type="button" wire:click="cancel">Cancelar venda</button>
                </div>
            </div>
        </div>
    @endif
</div>
