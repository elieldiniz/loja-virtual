<?php

use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleChannel;
use App\Models\SaleItem;
use App\Services\Dashboard\DashboardMetrics;
use App\Services\Dashboard\Period;
use Carbon\CarbonImmutable;
use Database\Seeders\LookupSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    Carbon::setTestNow('2026-10-19 12:00:00');
    $this->metrics = new DashboardMetrics;
    $this->period = Period::last(30);
});

afterEach(fn () => Carbon::setTestNow());

function dashSale(array $attrs = [], array $items = [['name' => 'Colar', 'qty' => 1, 'price' => 100]]): Sale
{
    $subtotal = collect($items)->sum(fn ($i) => $i['qty'] * $i['price']);
    $sale = Sale::factory()->create($attrs + ['subtotal' => $subtotal, 'total' => $subtotal, 'sold_at' => now()]);

    foreach ($items as $item) {
        SaleItem::factory()->create([
            'sale_id' => $sale->id, 'product_name' => $item['name'], 'quantity' => $item['qty'],
            'unit_price' => $item['price'], 'line_total' => $item['qty'] * $item['price'], 'category_name' => $item['cat'] ?? 'Colares',
        ]);
    }

    return $sale;
}

it('resolves 7, 30 and 90 day periods including today and the previous period', function () {
    $p = Period::last(30, CarbonImmutable::parse('2026-09-30'));

    expect($p->start->toDateString())->toBe('2026-09-01')
        ->and($p->end->toDateString())->toBe('2026-09-30')
        ->and($p->previousStart->toDateString())->toBe('2026-08-02')
        ->and($p->previousEnd->toDateString())->toBe('2026-08-31');

    $week = Period::last(7, CarbonImmutable::parse('2026-10-19'));
    expect($week->start->toDateString())->toBe('2026-10-13')->and($week->previousStart->toDateString())->toBe('2026-10-06')->and($week->previousEnd->toDateString())->toBe('2026-10-12');

    expect(Period::last(12)->days)->toBe(30);
});

it('calculates revenue, sales, average and pieces ignoring cancelled sales', function () {
    dashSale([], [['name' => 'A', 'qty' => 2, 'price' => 100]]);
    dashSale([], [['name' => 'B', 'qty' => 1, 'price' => 50]]);
    dashSale(['cancelled_at' => now(), 'cancel_reason' => 'x'], [['name' => 'C', 'qty' => 5, 'price' => 900]]);

    $t = $this->metrics->totals($this->period->start, $this->period->end);

    expect($t['revenue']->amount())->toBe(25000)
        ->and($t['count'])->toBe(2)
        ->and($t['average']->amount())->toBe(12500)
        ->and($t['pieces'])->toBe(3);
});

it('excludes sales outside the period from the totals', function () {
    dashSale(['sold_at' => now()->subDays(40)]);

    expect($this->metrics->totals($this->period->start, $this->period->end)['count'])->toBe(0);
});

it('computes the variation against the previous period without dividing by zero', function () {
    expect($this->metrics->delta(150, 100))->toBe(['direction' => 'up', 'percent' => 50])
        ->and($this->metrics->delta(50, 100))->toBe(['direction' => 'down', 'percent' => 50])
        ->and($this->metrics->delta(100, 100))->toBe(['direction' => 'up', 'percent' => 0])
        ->and($this->metrics->delta(10, 0))->toBeNull();
});

it('builds a daily series filling empty days with zero', function () {
    dashSale(['sold_at' => now()->subDays(2)], [['name' => 'A', 'qty' => 2, 'price' => 50]]);
    dashSale(['sold_at' => now()], [['name' => 'B', 'qty' => 1, 'price' => 30]]);

    $series = $this->metrics->series(Period::last(7));

    expect($series)->toHaveCount(7)
        ->and($series[4]['revenue'])->toBe(100.0)->and($series[4]['count'])->toBe(1)->and($series[4]['pieces'])->toBe(2)
        ->and($series[5]['revenue'])->toBe(0.0)
        ->and($series[6]['revenue'])->toBe(30.0)
        ->and(collect($series)->sum('revenue'))->toBe(130.0);
});

it('groups 90 days by week and keeps the totals', function () {
    dashSale(['sold_at' => now()->subDays(80)]);
    dashSale(['sold_at' => now()]);

    $series = $this->metrics->series(Period::last(90));

    expect($series)->toHaveCount(13)
        ->and($series[0]['range'])->toContain(' a ')
        ->and(collect($series)->sum('revenue'))->toBe(200.0)
        ->and(collect($series)->sum('count'))->toBe(2);
});

it('ranks the top 6 pieces by quantity allocating the sale discount proportionally', function () {
    // venda de 2 itens (R$ 100 e R$ 50) com R$ 15 de desconto: o desconto é rateado 10 e 5
    dashSale(['total' => 135], [['name' => 'Colar', 'qty' => 1, 'price' => 100], ['name' => 'Argola', 'qty' => 1, 'price' => 50]]);

    $top = $this->metrics->topProducts($this->period->start, $this->period->end);

    $byName = collect($top)->keyBy('name');
    expect($byName['Colar']['revenue']->amount())->toBe(9000)
        ->and($byName['Argola']['revenue']->amount())->toBe(4500);
});

it('returns only 6 pieces ordered by quantity and then revenue', function () {
    foreach (range(1, 7) as $i) {
        dashSale([], [['name' => "Peça {$i}", 'qty' => $i, 'price' => 10]]);
    }

    $top = $this->metrics->topProducts($this->period->start, $this->period->end);

    expect($top)->toHaveCount(6)->and($top[0]['name'])->toBe('Peça 7')->and($top[5]['name'])->toBe('Peça 2');
});

it('splits revenue by category with the same allocation and sums to the period revenue', function () {
    dashSale(['total' => 135], [['name' => 'Colar', 'qty' => 1, 'price' => 100, 'cat' => 'Colares'], ['name' => 'Argola', 'qty' => 1, 'price' => 50, 'cat' => 'Brincos']]);
    dashSale([], [['name' => 'Outro', 'qty' => 1, 'price' => 65, 'cat' => 'Colares']]);

    $cats = collect($this->metrics->revenueByCategory($this->period->start, $this->period->end))->keyBy('name');
    $revenue = $this->metrics->totals($this->period->start, $this->period->end)['revenue'];

    expect($cats['Colares']['revenue']->amount())->toBe(15500)
        ->and($cats['Brincos']['revenue']->amount())->toBe(4500)
        ->and($cats->sum(fn ($c) => $c['revenue']->amount()))->toBe($revenue->amount());
});

it('separates cash from installment sales and sums by payment method', function () {
    $pix = PaymentMethod::where('slug', 'pix')->first();
    $credito = PaymentMethod::where('slug', 'credito')->first();
    dashSale(['payment_method_id' => $pix->id, 'installments_count' => 1, 'total' => 100, 'subtotal' => 100]);
    dashSale(['payment_method_id' => $credito->id, 'installments_count' => 3, 'total' => 200, 'subtotal' => 200]);
    dashSale(['payment_method_id' => $credito->id, 'installments_count' => 1, 'total' => 50, 'subtotal' => 50]);

    $b = $this->metrics->paymentBreakdown($this->period->start, $this->period->end);
    $methods = collect($b['methods'])->keyBy('name');

    expect($b['cash']->amount())->toBe(15000)->and($b['installment']->amount())->toBe(20000)
        ->and($methods['Pix']['revenue']->amount())->toBe(10000)
        ->and($methods['Crédito']['revenue']->amount())->toBe(25000)
        ->and($methods['Dinheiro']['revenue']->amount())->toBe(0);
});

it('counts sales per channel', function () {
    $insta = SaleChannel::where('slug', 'instagram')->first();
    dashSale(['sale_channel_id' => $insta->id]);
    dashSale(['sale_channel_id' => $insta->id]);
    dashSale();

    $channels = collect($this->metrics->channels($this->period->start, $this->period->end))->keyBy('name');

    expect($channels['Instagram']['count'])->toBe(2)->and($channels['WhatsApp']['count'])->toBe(1)->and($channels['Loja física']['count'])->toBe(0);
});

it('lists the top 5 customers by value ignoring sales without customer and cancelled ones', function () {
    foreach (range(1, 6) as $i) {
        $c = Customer::factory()->create(['name' => "Cliente {$i}"]);
        dashSale(['customer_id' => $c->id, 'total' => $i * 100, 'subtotal' => $i * 100]);
    }
    dashSale(['total' => 9999, 'subtotal' => 9999]);
    dashSale(['customer_id' => Customer::first()->id, 'cancelled_at' => now(), 'total' => 5000, 'subtotal' => 5000]);

    $top = $this->metrics->topCustomers($this->period->start, $this->period->end);

    expect($top)->toHaveCount(5)->and($top[0]['name'])->toBe('Cliente 6')->and($top[4]['name'])->toBe('Cliente 2')
        ->and($top[0]['total']->amount())->toBe(60000);
});
