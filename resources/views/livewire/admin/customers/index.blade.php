<div>
    <div class="page-head">
        <div><h1>Clientes</h1><p>Cadastro, compras e crediário de cada cliente.</p></div>
        <a class="btn btn-primary" href="{{ route('admin.customers.create') }}"><x-icon name="plus" />Nova cliente</a>
    </div>

    <div class="stats">
        <div class="card stat"><b>{{ $registered }}</b><span>Clientes cadastradas</span></div>
        <div class="card stat"><b>{{ $activeLast30 }}</b><span>Compraram nos últimos 30 dias</span></div>
        <div class="card stat"><b>{{ $receivable->format() }}</b><span>A receber no crediário</span></div>
        <div class="card stat"><b>{{ $overdue }}</b><span><x-icon name="alert" /> Parcelas vencidas</span></div>
    </div>

    <div class="card toolbar">
        <div class="field-inline"><x-icon name="search" /><input class="input" type="search" wire:model.live.debounce.250ms="search" placeholder="Buscar por nome ou telefone" aria-label="Buscar cliente"></div>
        <div class="seg" role="group" aria-label="Filtro">
            <button type="button" wire:click="setFilter('all')" aria-pressed="{{ $filter !== 'open' ? 'true' : 'false' }}">Todas</button>
            <button type="button" wire:click="setFilter('open')" aria-pressed="{{ $filter === 'open' ? 'true' : 'false' }}">Com parcelas em aberto</button>
        </div>
    </div>

    <div class="card">
        @forelse ($customers as $customer)
            @php $summary = \App\Services\CustomerSummary::from($customer); @endphp
            <a class="crow" href="{{ route('admin.customers.edit', $customer) }}" wire:key="customer-{{ $customer->id }}">
                <span class="avatar">{{ $customer->initials() }}</span>
                <span>
                    <span class="row-title">{{ $customer->name }}</span>
                    <span class="row-sub">{{ $customer->whatsapp ? $customer->whatsappFormatted() : 'Sem telefone' }}@if ($customer->city) · {{ $customer->city }}@endif</span>
                    <span class="row-meta">
                        @if ($summary->purchases)
                            <span class="pill">{{ $summary->purchases }} {{ $summary->purchases === 1 ? 'compra' : 'compras' }}</span>
                            <span class="pill">Última {{ $summary->lastPurchaseAt->format('d/m') }}</span>
                        @else
                            <span class="pill off">Ainda não comprou</span>
                        @endif
                        @if ($summary->overdueCount)
                            <span class="pill late"><x-icon name="alert" />{{ $summary->overdueCount }} {{ $summary->overdueCount === 1 ? 'parcela vencida' : 'parcelas vencidas' }}</span>
                        @endif
                    </span>
                </span>
                <span class="crow-r">
                    <span class="lbl">Total comprado</span><b>{{ $summary->total->format() }}</b>
                    @if (! $summary->openBalance->isZero())<span class="owes">Deve {{ $summary->openBalance->format() }}</span>@endif
                </span>
            </a>
        @empty
            <div class="empty">Nenhuma cliente encontrada.</div>
        @endforelse

        @if ($total > $customers->count())
            <button class="more-btn" wire:click="showMore">Mostrar mais {{ min(\App\Livewire\Admin\Customers\Index::PER_PAGE, $total - $customers->count()) }} de {{ $total - $customers->count() }} restantes</button>
        @endif
    </div>
</div>
