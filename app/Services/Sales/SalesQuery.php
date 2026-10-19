<?php

namespace App\Services\Sales;

use App\Models\Installment;
use App\Models\Sale;
use App\Support\Money;
use App\Support\SearchText;
use Illuminate\Database\Eloquent\Builder;

/** Consulta da lista de vendas: período, pagamento, situação e busca. */
class SalesQuery
{
    public const PERIODS = ['today' => 1, '7' => 7, '30' => 30, 'all' => null];

    /** @return Builder<Sale> */
    public function filtered(string $period, ?string $paymentSlug, bool $onlyCancelled, string $search): Builder
    {
        $days = array_key_exists($period, self::PERIODS) ? self::PERIODS[$period] : self::PERIODS['today'];

        return Sale::query()
            ->when($days !== null, fn ($q) => $q->where('sold_at', '>=', today()->subDays($days - 1)->startOfDay()))
            ->when($paymentSlug, fn ($q) => $q->whereHas('paymentMethod', fn ($m) => $m->where('slug', $paymentSlug)))
            ->when($onlyCancelled, fn ($q) => $q->whereNotNull('cancelled_at'))
            ->when(trim($search) !== '', function ($q) use ($search) {
                $text = SearchText::normalize(ltrim(trim($search), '#'));
                $digits = preg_replace('/\D+/', '', $search);

                $q->where(function ($w) use ($text, $digits) {
                    if ($digits !== '') {
                        $w->orWhereRaw("lpad(sales.number::text, 4, '0') like ?", ['%'.$digits.'%']);
                    }

                    $w->orWhereHas('customer', fn ($c) => $c->whereRaw(SearchText::sqlNormalized('customers.name').' like ?', ['%'.$text.'%']))
                        ->orWhereHas('items', fn ($i) => $i->whereRaw(SearchText::sqlNormalized('sale_items.product_name').' like ?', ['%'.$text.'%']));
                });
            })
            ->latest('sold_at')->latest('id');
    }

    /**
     * Resumo do filtro: vendas (e canceladas), faturamento, ticket médio e quanto falta receber.
     *
     * @param  Builder<Sale>  $filtered
     * @return array{count: int, cancelled: int, revenue: Money, average: Money, receivable: Money}
     */
    public function summary(Builder $filtered): array
    {
        $base = (clone $filtered)->reorder();
        $active = (clone $base)->whereNull('cancelled_at');

        $count = (clone $active)->count();
        $revenue = Money::from((clone $active)->sum('total'));

        return [
            'count' => $count,
            'cancelled' => (clone $base)->whereNotNull('cancelled_at')->count(),
            'revenue' => $revenue,
            'average' => $count > 0 ? $revenue->divideRound($count) : Money::zero(),
            'receivable' => Money::from(
                Installment::query()->whereNull('paid_at')->whereIn('sale_id', (clone $active)->select('sales.id'))->sum('amount'),
            ),
        ];
    }
}
