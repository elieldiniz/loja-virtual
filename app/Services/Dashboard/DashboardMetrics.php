<?php

namespace App\Services\Dashboard;

use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Métricas do Painel. Só vendas registradas e não canceladas entram nos números. */
class DashboardMetrics
{
    /** @return array{revenue: Money, count: int, average: Money, pieces: int} */
    public function totals(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $sales = $this->sales($start, $end);
        $count = (clone $sales)->count();
        $revenue = Money::from((clone $sales)->sum('sales.total'));
        $pieces = (int) DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.cancelled_at')
            ->whereBetween('sales.sold_at', [$start, $end])
            ->sum('sale_items.quantity');

        return [
            'revenue' => $revenue,
            'count' => $count,
            'average' => $count > 0 ? $revenue->divideRound($count) : Money::zero(),
            'pieces' => $pieces,
        ];
    }

    /**
     * Variação em relação ao período anterior.
     *
     * @return array{direction: 'up'|'down'|'none', percent: int}|null null quando o período anterior é zero
     */
    public function delta(int|float $current, int|float $previous): ?array
    {
        if ($previous == 0) {
            return null;
        }

        $change = ($current - $previous) / $previous * 100;

        return ['direction' => $change >= 0 ? 'up' : 'down', 'percent' => (int) round(abs($change))];
    }

    /**
     * Série do gráfico: por dia (7 e 30 dias) ou por semana (90 dias). Dias sem venda aparecem com zero.
     *
     * @return list<array{label: string, range: ?string, revenue: float, count: int, pieces: int}>
     */
    public function series(Period $period): array
    {
        $rows = $this->sales($period->start, $period->end)
            ->leftJoin(DB::raw('(select sale_id, sum(quantity) as pieces from sale_items group by sale_id) as si'), 'si.sale_id', '=', 'sales.id')
            ->selectRaw('date(sales.sold_at) as day, count(*) as sales_count, coalesce(sum(sales.total), 0) as revenue, coalesce(sum(si.pieces), 0) as pieces')
            ->groupBy('day')->get()->keyBy('day');

        $days = [];
        for ($i = 0; $i < $period->days; $i++) {
            $date = $period->start->addDays($i);
            $row = $rows->get($date->toDateString());

            $days[] = [
                'date' => $date,
                'revenue' => $row ? (float) $row->revenue : 0.0,
                'count' => $row ? (int) $row->sales_count : 0,
                'pieces' => $row ? (int) $row->pieces : 0,
            ];
        }

        if ($period->days !== 90) {
            return array_map(fn (array $d) => [
                'label' => $d['date']->format('d/m'), 'range' => null,
                'revenue' => $d['revenue'], 'count' => $d['count'], 'pieces' => $d['pieces'],
            ], $days);
        }

        return collect($days)->chunk(7)->map(function (Collection $week) {
            $first = $week->first()['date']->format('d/m');
            $last = $week->last()['date']->format('d/m');

            return [
                'label' => $first, 'range' => "{$first} a {$last}",
                'revenue' => round($week->sum('revenue'), 2), 'count' => $week->sum('count'), 'pieces' => $week->sum('pieces'),
            ];
        })->values()->all();
    }

    /**
     * Peças mais vendidas (top N por quantidade). O desconto da venda é distribuído
     * proporcionalmente entre os itens para calcular o faturamento da peça.
     *
     * @return list<array{name: string, quantity: int, revenue: Money, product_id: ?int}>
     */
    public function topProducts(CarbonImmutable $start, CarbonImmutable $end, int $limit = 6): array
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.cancelled_at')
            ->whereBetween('sales.sold_at', [$start, $end])
            ->selectRaw('max(sale_items.product_id) as product_id, max(sale_items.product_name) as name, sum(sale_items.quantity) as quantity, '.$this->allocatedRevenue().' as revenue')
            ->groupByRaw('sale_items.product_id, (case when sale_items.product_id is null then sale_items.product_name end)')
            ->orderByDesc('quantity')->orderByDesc('revenue')->orderBy('name')
            ->limit($limit)->get()
            ->map(fn ($r) => [
                'name' => $r->name, 'product_id' => $r->product_id ? (int) $r->product_id : null,
                'quantity' => (int) $r->quantity, 'revenue' => Money::from(round((float) $r->revenue, 2)),
            ])->all();
    }

    /**
     * Faturamento por categoria, com o mesmo rateio do desconto.
     *
     * @return list<array{name: string, revenue: Money}>
     */
    public function revenueByCategory(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.cancelled_at')
            ->whereBetween('sales.sold_at', [$start, $end])
            ->selectRaw("coalesce(sale_items.category_name, 'Sem categoria') as name, ".$this->allocatedRevenue().' as revenue')
            ->groupBy('name')->orderByDesc('revenue')->get()
            ->map(fn ($r) => ['name' => $r->name, 'revenue' => Money::from(round((float) $r->revenue, 2))])
            ->filter(fn ($r) => $r['revenue']->amount() > 0)->values()->all();
    }

    /**
     * Como pagaram: à vista × parcelado e por forma de pagamento (valores do faturamento).
     *
     * @return array{cash: Money, installment: Money, methods: list<array{name: string, revenue: Money}>}
     */
    public function paymentBreakdown(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $sales = $this->sales($start, $end);

        $cash = Money::from((clone $sales)->where('sales.installments_count', '<=', 1)->sum('sales.total'));
        $installment = Money::from((clone $sales)->where('sales.installments_count', '>', 1)->sum('sales.total'));

        $methods = DB::table('payment_methods')
            ->leftJoin('sales', function ($join) use ($start, $end) {
                $join->on('sales.payment_method_id', '=', 'payment_methods.id')
                    ->whereNull('sales.cancelled_at')
                    ->whereBetween('sales.sold_at', [$start, $end]);
            })
            ->selectRaw('payment_methods.name, coalesce(sum(sales.total), 0) as revenue')
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->orderByDesc('revenue')->orderBy('payment_methods.id')->get()
            ->map(fn ($r) => ['name' => $r->name, 'revenue' => Money::from((float) $r->revenue)])->all();

        return ['cash' => $cash, 'installment' => $installment, 'methods' => $methods];
    }

    /** @return list<array{name: string, count: int}> */
    public function channels(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return DB::table('sale_channels')
            ->leftJoin('sales', function ($join) use ($start, $end) {
                $join->on('sales.sale_channel_id', '=', 'sale_channels.id')
                    ->whereNull('sales.cancelled_at')
                    ->whereBetween('sales.sold_at', [$start, $end]);
            })
            ->selectRaw('sale_channels.name, count(sales.id) as sales_count')
            ->groupBy('sale_channels.id', 'sale_channels.name')
            ->orderByDesc('sales_count')->orderBy('sale_channels.id')->get()
            ->map(fn ($r) => ['name' => $r->name, 'count' => (int) $r->sales_count])->all();
    }

    /** @return list<array{id: int, name: string, total: Money, purchases: int}> */
    public function topCustomers(CarbonImmutable $start, CarbonImmutable $end, int $limit = 5): array
    {
        return $this->sales($start, $end)
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->selectRaw('customers.id, customers.name, sum(sales.total) as total, count(*) as purchases')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total')->orderBy('customers.name')->limit($limit)->get()
            ->map(fn ($r) => ['id' => (int) $r->id, 'name' => $r->name, 'total' => Money::from((float) $r->total), 'purchases' => (int) $r->purchases])->all();
    }

    /** @return Builder */
    private function sales(CarbonImmutable $start, CarbonImmutable $end)
    {
        return DB::table('sales')->whereNull('sales.cancelled_at')->whereBetween('sales.sold_at', [$start, $end]);
    }

    /** Faturamento do item com o desconto da venda distribuído proporcionalmente. */
    private function allocatedRevenue(): string
    {
        return 'coalesce(sum(case when sales.subtotal > 0 then sale_items.line_total * sales.total / sales.subtotal else 0 end), 0)';
    }
}
