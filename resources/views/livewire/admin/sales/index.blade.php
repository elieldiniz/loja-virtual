@php
    use App\Services\MessageBuilder;
    use App\Services\SaleNumberGenerator;
    use App\Support\Format;
    use App\Support\Money;
    $builder = app(MessageBuilder::class);
@endphp
<div>
    <div class="page-head">
        <div><h1>Vendas</h1><p>Todas as vendas registradas. Clique em uma para ver os detalhes.</p></div>
        <a class="btn btn-primary" href="{{ route('admin.sales.create') }}"><x-icon name="plus" />Nova venda</a>
    </div>

    <div class="card toolbar">
        <div class="seg" role="group" aria-label="Período">
            @foreach (['today' => 'Hoje', '7' => '7 dias', '30' => '30 dias', 'all' => 'Tudo'] as $key => $label)
                <button type="button" wire:click="setPeriod('{{ $key }}')" aria-pressed="{{ $period === (string) $key ? 'true' : 'false' }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="field-inline"><x-icon name="search" /><input class="input" type="search" wire:model.live.debounce.250ms="search" placeholder="Cliente, peça ou nº da venda" aria-label="Buscar venda"></div>
        <select class="select" id="spay" wire:model.live="payment" aria-label="Forma de pagamento">
            <option value="">Todos os pagamentos</option>
            @foreach ($methods as $method)<option value="{{ $method->slug }}">{{ $method->name }}</option>@endforeach
        </select>
        <select class="select" id="sstatus" wire:model.live="status" aria-label="Situação">
            <option value="">Todas as situações</option>
            <option value="canceled">Só canceladas</option>
        </select>
    </div>

    <div class="stats">
        <div class="card stat"><b>{{ $summary['count'] }}</b><span>Vendas{{ $summary['cancelled'] ? ' · '.$summary['cancelled'].' '.($summary['cancelled'] === 1 ? 'cancelada' : 'canceladas') : '' }}</span></div>
        <div class="card stat"><b>{{ $summary['revenue']->format() }}</b><span>Faturamento</span></div>
        <div class="card stat"><b>{{ $summary['average']->format() }}</b><span>Ticket médio</span></div>
        <div class="card stat"><b>{{ $summary['receivable']->format() }}</b><span>A receber destas vendas</span></div>
    </div>

    <div class="card slist">
        @forelse ($groups as $group)
            <div class="dhead" wire:key="day-{{ $group['date']->toDateString() }}">
                <span>{{ Format::dayTitle($group['date']) }}</span>
                <span>{{ $group['count'] }} {{ $group['count'] === 1 ? 'venda' : 'vendas' }} · <b>{{ $group['total']->format() }}</b></span>
            </div>
            @foreach ($group['sales'] as $sale)
                @php $pieces = $sale->items->sum('quantity'); @endphp
                <a class="srow {{ $sale->isCancelled() ? 'is-canc' : '' }}" href="{{ route('admin.sales.show', $sale) }}" wire:key="sale-{{ $sale->id }}">
                    <span class="s-time">{{ $sale->sold_at->format('H:i') }}<small>{{ SaleNumberGenerator::format($sale->number) }}</small></span>
                    <span class="s-main"><b>{!! $sale->customer ? e($sale->customer->name) : '<span class="anon">Sem cliente</span>' !!}</b><small>{{ $sale->items->map(fn ($i) => ($i->quantity > 1 ? $i->quantity.'× ' : '').$i->product_name)->implode(' + ') }}</small></span>
                    <span class="s-pay">{{ $builder->paymentLabel($sale) }}<small>{{ Format::pieces($pieces) }} · {{ $sale->channel->name }}</small></span>
                    <span class="s-tot">@if ($sale->isCancelled())<span class="pill canc">Cancelada</span>@endif<b>{{ Money::from($sale->total)->format() }}</b></span>
                    <x-icon name="right" />
                </a>
            @endforeach
        @empty
            <div class="empty">
                @if ($period === 'today' && ! $hasFilters)
                    Nenhuma venda hoje ainda.<br><a class="btn btn-primary" href="{{ route('admin.sales.create') }}" style="margin-top:12px"><x-icon name="plus" />Registrar a primeira</a>
                @else
                    Nenhuma venda encontrada com esses filtros.
                @endif
            </div>
        @endforelse

        @if ($remaining > 0)
            <button class="more-btn" wire:click="showMore">Mostrar mais {{ min(\App\Livewire\Admin\Sales\Index::PER_PAGE, $remaining) }} de {{ $remaining }} restantes</button>
        @endif
    </div>
</div>
