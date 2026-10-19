<div>
@if (! $isEditing)
    <div class="page-head">
        <div><h1>Nova cliente</h1><p>Só o nome é obrigatório.</p></div>
        <a class="btn btn-ghost" href="{{ route('admin.customers') }}"><x-icon name="left" />Voltar</a>
    </div>
    <div class="client-grid"><div>@include('livewire.admin.customers.partials.form-fields')</div></div>
@else
    @php
        $wa = $customer->whatsapp ? \App\Services\WhatsAppLink::open($customer->whatsapp) : null;
        $since = $summary->firstPurchaseAt ?? $customer->created_at;
    @endphp
    <div class="page-head">
        <div class="client-head">
            <span class="avatar lg">{{ $customer->initials() }}</span>
            <div><h1>{{ $customer->name }}</h1><p>Cliente desde {{ $since->format('d/m/Y') }}@if ($customer->city) · {{ $customer->city }}@endif</p></div>
        </div>
        <div class="head-acts">
            @if ($wa)<a class="btn btn-ghost" href="{{ $wa }}" target="_blank" rel="noopener"><x-icon name="wa" />WhatsApp</a>@endif
            <a class="btn btn-primary" href="{{ route('admin.sales.create', ['cliente' => $customer->id]) }}"><x-icon name="plus" />Nova venda</a>
            <a class="btn btn-ghost" href="{{ route('admin.customers') }}"><x-icon name="left" />Voltar</a>
        </div>
    </div>

    <div class="stats">
        <div class="card stat"><b>{{ $summary->total->format() }}</b><span>Total comprado</span></div>
        <div class="card stat"><b>{{ $summary->purchases }}</b><span>Compras</span></div>
        <div class="card stat"><b>{{ $summary->average->format() }}</b><span>Ticket médio</span></div>
        <div class="card stat">
            <b @class(['late-n' => $summary->overdueCount])>{{ $summary->openBalance->format() }}</b>
            <span>@if ($summary->overdueCount)<x-icon name="alert" />{{ $summary->overdueCount }} {{ $summary->overdueCount === 1 ? 'vencida' : 'vencidas' }}@else Em aberto no crediário @endif</span>
        </div>
    </div>

    <div class="client-grid">
        <div class="col">
            <section class="card box">
                <h2>Crediário</h2>
                @if ($parcels->isNotEmpty())
                    <div class="parcels">
                        @foreach ($parcels as $installment)<x-parcel-row :installment="$installment" wire:key="parcel-{{ $installment->id }}" />@endforeach
                    </div>
                    @if ($hiddenPaid)
                        <button class="tbl-toggle" type="button" wire:click="$toggle('showAllParcels')">{{ $showAllParcels ? 'Esconder parcelas pagas antigas' : 'Ver todas as parcelas pagas' }}</button>
                    @endif
                @else
                    <p class="hint">Nenhuma compra no crediário.</p>
                @endif
            </section>

            <section class="card box">
                <h2>Compras</h2>
                @if ($hasPurchases)
                    <div class="sales">
                        @foreach ($purchases as $sale)<x-sale-line :sale="$sale" :show-customer="false" />@endforeach
                    </div>
                    @if ($summary->purchases > 8)
                        <button class="tbl-toggle" type="button" wire:click="$toggle('showAllSales')">{{ $showAllSales ? 'Mostrar menos' : 'Ver todas as '.$summary->purchases.' compras' }}</button>
                    @endif
                @else
                    <p class="hint">Ainda não comprou.</p>
                @endif
            </section>
        </div>
        <div class="col">@include('livewire.admin.customers.partials.form-fields')</div>
    </div>
@endif

    @if ($confirmingDelete)
        <div class="sheet-bg" wire:click.self="closeConfirm">
            <div class="sheet" role="alertdialog" aria-modal="true" aria-labelledby="cfH">
                @if ($customer?->exists && $customer->sales()->exists())
                    <h3 id="cfH">Não dá para excluir {{ $customer->name }}</h3>
                    <p>Ela tem compras registradas e o histórico depende dela. Cadastros sem compras podem ser excluídos.</p>
                    <div class="acts"><button class="btn btn-primary" type="button" wire:click="closeConfirm">Entendi</button></div>
                @else
                    <h3 id="cfH">Excluir {{ $customer?->name }}?</h3>
                    <p>O cadastro será removido. Isso não pode ser desfeito.</p>
                    <div class="acts">
                        <button class="btn btn-ghost" type="button" wire:click="closeConfirm">Cancelar</button>
                        <button class="btn btn-primary" type="button" wire:click="delete">Excluir</button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
