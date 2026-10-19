@php
    use App\Support\Money;
    $metricTitles = ['revenue' => 'Faturamento', 'count' => 'Vendas', 'pieces' => 'Peças vendidas'];
    $percentOf = fn ($cents) => $revenue->amount() > 0 ? round($cents / $revenue->amount() * 100).'%' : '0%';
    $tip = fn (string $title, string $detail) => '<b>'.e($title).'</b><span>'.e($detail).'</span>';
@endphp
<div>
    <div class="page-head">
        <div><h1>Painel</h1><p>Vendas registradas nos últimos {{ $range }} dias.</p></div>
        <div class="head-acts">
            <div class="seg" role="group" aria-label="Período">
                @foreach ([7, 30, 90] as $n)
                    <button type="button" wire:click="setRange({{ $n }})" aria-pressed="{{ $range === $n ? 'true' : 'false' }}">{{ $n }} dias</button>
                @endforeach
            </div>
            <a class="btn btn-primary" href="{{ route('admin.sales.create') }}"><x-icon name="plus" />Registrar venda</a>
        </div>
    </div>

    <div class="stats">
        @foreach ($kpis as [$label, $value, $delta])
            <div class="card kpi">
                <span class="lbl">{{ $label }}</span>
                <b>{{ $value }}</b>
                @if ($delta)
                    <span class="delta {{ $delta['direction'] }}">{{ $delta['direction'] === 'up' ? '▲ +' : '▼ −' }}{{ $delta['percent'] }}% <small>vs. {{ $range }} dias antes</small></span>
                @else
                    <span class="delta"><small>sem dados do período anterior</small></span>
                @endif
            </div>
        @endforeach
    </div>

    <section class="card chart-card">
        <div class="chart-head">
            <div><h2>{{ $metricTitles[$metric] }}</h2><p>{{ $range === 90 ? 'Por semana' : 'Por dia' }} · passe o mouse para ver cada {{ $range === 90 ? 'semana' : 'dia' }}</p></div>
            <div class="mtabs" role="group" aria-label="Métrica">
                @foreach ($metricTitles as $key => $title)
                    <button type="button" wire:click="setMetric('{{ $key }}')" aria-pressed="{{ $metric === $key ? 'true' : 'false' }}">{{ $title }}</button>
                @endforeach
            </div>
        </div>
        <div wire:key="chart-{{ $range }}-{{ $metric }}" x-data="salesChart(@js($series), @js($metric))" x-init="draw()" x-on:resize.window.debounce.150ms="draw()">
            <div class="plot" x-ref="plot" aria-label="Gráfico de {{ mb_strtolower($metricTitles[$metric]) }}"></div>
        </div>
        <button class="tbl-toggle" type="button" wire:click="toggleTable">{{ $showTable ? 'Esconder tabela' : 'Ver como tabela' }}</button>
        @if ($showTable)
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead><tr><th>{{ $range === 90 ? 'Semana' : 'Dia' }}</th><th>Vendas</th><th>Peças</th><th>Faturamento</th></tr></thead>
                    <tbody>
                        @foreach ($series as $row)
                            <tr><td>{{ $row['range'] ?? $row['label'] }}</td><td>{{ $row['count'] }}</td><td>{{ $row['pieces'] }}</td><td>{{ Money::from($row['revenue'])->format() }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div class="dash-grid">
        <section class="card box">
            <div class="chart-head"><h2>A receber no crediário</h2><a class="btn btn-ghost" href="{{ route('admin.customers', ['filtro' => 'open']) }}">Ver clientes</a></div>
            <div class="recv">
                <div><span class="lbl">Em aberto</span><b>{{ $receivableTotal->format() }}</b><small>{{ $receivableCount }} {{ $receivableCount === 1 ? 'parcela' : 'parcelas' }}</small></div>
                <div class="{{ $overdueCount ? 'is-late' : '' }}"><span class="lbl">@if ($overdueCount)<x-icon name="alert" />@endif Vencido</span><b>{{ $overdueTotal->format() }}</b><small>{{ $overdueCount }} {{ $overdueCount === 1 ? 'parcela' : 'parcelas' }}</small></div>
            </div>
            <div class="parcels">
                @foreach ($nextInstallments as $installment)<x-parcel-row :installment="$installment" :show-client="true" wire:key="next-{{ $installment->id }}" />@endforeach
            </div>
        </section>
        <section class="card box">
            <h2>Melhores clientes</h2><p class="sub">Quem mais comprou no período.</p>
            @if ($bestCustomers)
                <x-hbars :rows="collect($bestCustomers)->map(fn ($r) => [
                    'name' => $r['name'], 'avatar' => collect(explode(' ', $r['name']))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''),
                    'value' => $r['total']->format(), 'n' => $r['total']->amount(),
                    'tip' => $tip($r['name'], $r['purchases'].' '.($r['purchases'] === 1 ? 'compra' : 'compras')),
                ])->all()" />
            @else
                <p class="hint">Nenhuma venda neste período.</p>
            @endif
        </section>
    </div>

    <div class="dash-grid">
        <section class="card box">
            <h2>Peças mais vendidas</h2><p class="sub">Em quantidade de peças.</p>
            @if ($topProducts)
                <x-hbars :rows="collect($topProducts)->map(fn ($r) => [
                    'name' => $r['name'], 'thumb_url' => ($covers->get($r['product_id'])?->cover()?->getUrl('thumb')),
                    'value' => $r['quantity'], 'n' => $r['quantity'],
                    'tip' => $tip($r['name'], $r['quantity'].' '.($r['quantity'] === 1 ? 'peça' : 'peças').' · '.$r['revenue']->format()),
                ])->all()" />
            @else
                <p class="hint">Nenhuma venda neste período.</p>
            @endif
        </section>
        <section class="card box">
            <h2>Faturamento por categoria</h2>
            @if ($categories)
                <x-hbars :rows="collect($categories)->map(fn ($r) => [
                    'name' => $r['name'], 'value' => $percentOf($r['revenue']->amount()), 'n' => $r['revenue']->amount(),
                    'tip' => $tip($r['name'], $r['revenue']->format()),
                ])->all()" />
            @else
                <p class="hint">Nenhuma venda neste período.</p>
            @endif
        </section>
    </div>

    <div class="dash-grid">
        <section class="card box">
            <h2>Como pagaram</h2><p class="sub">Parte do faturamento.</p>
            @if ($salesCount)
                <x-hbars :rows="[
                    ['name' => 'À vista', 'value' => $percentOf($payments['cash']->amount()), 'n' => $payments['cash']->amount(), 'tip' => $tip('À vista', $payments['cash']->format())],
                    ['name' => 'Parcelado', 'value' => $percentOf($payments['installment']->amount()), 'n' => $payments['installment']->amount(), 'tip' => $tip('Parcelado', $payments['installment']->format())],
                ]" />
                <div class="divider"></div>
                <x-hbars :rows="collect($payments['methods'])->map(fn ($r) => [
                    'name' => $r['name'], 'value' => $percentOf($r['revenue']->amount()), 'n' => $r['revenue']->amount(),
                    'tip' => $tip($r['name'], $r['revenue']->format()),
                ])->all()" />
            @else
                <p class="hint">Nenhuma venda neste período.</p>
            @endif
        </section>
        <div class="col">
            <section class="card box">
                <h2>Onde vendeu</h2><p class="sub">Número de vendas.</p>
                @if ($salesCount)
                    <x-hbars :rows="collect($channels)->map(fn ($r) => [
                        'name' => $r['name'], 'value' => $r['count'], 'n' => $r['count'],
                        'tip' => $tip($r['name'], $r['count'].' '.($r['count'] === 1 ? 'venda' : 'vendas').' · '.round($r['count'] / $salesCount * 100).'%'),
                    ])->all()" />
                @else
                    <p class="hint">Nenhuma venda neste período.</p>
                @endif
            </section>

            <section class="card box">
                <h2>Aniversariantes de {{ $monthName }}</h2>
                <p class="sub">Mande os parabéns com {{ $giftPercent }}% de desconto de presente.</p>
                @forelse ($birthdays as $row)
                    @php $customer = $row['customer']; @endphp
                    <div class="bd {{ $row['diff'] < 0 ? 'past' : '' }}" wire:key="bd-{{ $customer->id }}">
                        <span class="avatar sm">{{ $customer->initials() }}</span>
                        <span class="bd-t"><a class="who-link" href="{{ route('admin.customers.edit', $customer) }}">{{ $customer->name }}</a><small>{{ $customer->birthdayLabel() }} · @if ($row['diff'] === 0)<b class="bd-today">Hoje 🎉</b>@else{{ $row['label'] }}@endif</small></span>
                        @if ($row['whatsapp'])<a class="btn btn-ghost bd-btn" href="{{ $row['whatsapp'] }}" target="_blank" rel="noopener"><x-icon name="wa" />Parabéns</a>@endif
                    </div>
                @empty
                    <p class="hint">Nenhuma cliente faz aniversário este mês.</p>
                @endforelse
            </section>
        </div>
    </div>

    <section class="card box">
        <div class="chart-head"><h2>Últimas vendas</h2><a class="btn btn-ghost" href="{{ route('admin.sales') }}">Ver todas</a></div>
        <div class="sales">
            @forelse ($recentSales as $sale)<x-sale-line :sale="$sale" />@empty<p class="hint">Nenhuma venda registrada ainda.</p>@endforelse
        </div>
    </section>
</div>
