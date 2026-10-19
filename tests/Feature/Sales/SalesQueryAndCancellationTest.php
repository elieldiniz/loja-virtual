<?php

use App\Models\Customer;
use App\Models\Installment;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Receivables;
use App\Services\Sales\SaleCancellation;
use App\Services\Sales\SalesQuery;
use Database\Seeders\LookupSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->seed(LookupSeeder::class));

it('cannot edit a registered sale', function () {
    $sale = Sale::factory()->create();

    expect(fn () => $sale->update(['total' => 1]))->toThrow(LogicException::class);
    expect($sale->fresh()->total)->not->toBe('1.00');
});

it('requires a reason to cancel and records date and reason', function () {
    $sale = Sale::factory()->create();
    $service = new SaleCancellation;

    expect(fn () => $service->cancel($sale, '  '))->toThrow(ValidationException::class);
    expect($sale->fresh()->isCancelled())->toBeFalse();

    $service->cancel($sale, 'Cliente desistiu');

    $fresh = $sale->fresh();
    expect($fresh->isCancelled())->toBeTrue()->and($fresh->cancel_reason)->toBe('Cliente desistiu')->and($fresh->cancelled_at)->not->toBeNull();
});

it('returns the amount already received so the store can arrange the refund', function () {
    $sale = Sale::factory()->create();
    Installment::factory()->paid()->create(['sale_id' => $sale->id, 'number' => 1, 'amount' => 33.33]);
    Installment::factory()->create(['sale_id' => $sale->id, 'number' => 2, 'amount' => 33.33]);

    expect((new SaleCancellation)->cancel($sale, 'Engano')->format())->toBe('R$ 33,33');
});

it('removes the sale from totals and receivables when cancelled and restores it when reactivated', function () {
    $customer = Customer::factory()->create();
    $sale = Sale::factory()->create(['customer_id' => $customer->id, 'total' => 100]);
    Installment::factory()->dueIn(10)->create(['sale_id' => $sale->id, 'amount' => 100]);

    $service = new SaleCancellation;
    $service->cancel($sale, 'Engano');

    $summary = Customer::query()->withSummary()->find($customer->id);
    expect($summary->purchases_count)->toBe(0)->and((float) $summary->open_balance)->toBe(0.0);
    expect((new Receivables)->openTotal()->amount())->toBe(0);

    $service->reactivate($sale->fresh());

    $summary = Customer::query()->withSummary()->find($customer->id);
    expect($summary->purchases_count)->toBe(1)->and((float) $summary->open_balance)->toBe(100.0);
});

it('filters sales by period', function () {
    $today = Sale::factory()->create(['sold_at' => now()]);
    $week = Sale::factory()->create(['sold_at' => now()->subDays(6)]);
    $month = Sale::factory()->create(['sold_at' => now()->subDays(29)]);
    $old = Sale::factory()->create(['sold_at' => now()->subDays(60)]);
    $query = new SalesQuery;

    $ids = fn (string $p) => $query->filtered($p, null, false, '')->pluck('id')->all();

    expect($ids('today'))->toBe([$today->id])
        ->and($ids('7'))->toEqualCanonicalizing([$today->id, $week->id])
        ->and($ids('30'))->toEqualCanonicalizing([$today->id, $week->id, $month->id])
        ->and($ids('all'))->toHaveCount(4);
});

it('filters by payment method and only cancelled', function () {
    $pix = PaymentMethod::where('slug', 'pix')->first();
    $cash = PaymentMethod::where('slug', 'dinheiro')->first();
    $a = Sale::factory()->create(['payment_method_id' => $pix->id]);
    $b = Sale::factory()->cancelled()->create(['payment_method_id' => $cash->id]);
    $query = new SalesQuery;

    expect($query->filtered('today', 'pix', false, '')->pluck('id')->all())->toBe([$a->id])
        ->and($query->filtered('today', null, true, '')->pluck('id')->all())->toBe([$b->id]);
});

it('searches by customer, piece and sale number', function () {
    $customer = Customer::factory()->create(['name' => 'Patrícia Gomes']);
    $a = Sale::factory()->create(['customer_id' => $customer->id, 'number' => 616]);
    SaleItem::factory()->create(['sale_id' => $a->id, 'product_name' => 'Colar Coração']);
    $b = Sale::factory()->create(['number' => 617]);
    SaleItem::factory()->create(['sale_id' => $b->id, 'product_name' => 'Argola']);
    $query = new SalesQuery;

    $find = fn (string $s) => $query->filtered('all', null, false, $s)->pluck('id')->all();

    expect($find('patricia'))->toBe([$a->id])
        ->and($find('coracao'))->toBe([$a->id])
        ->and($find('#0616'))->toBe([$a->id])
        ->and($find('617'))->toBe([$b->id])
        ->and($find('inexistente'))->toBe([]);
});

it('summarizes the filter ignoring cancelled sales', function () {
    $a = Sale::factory()->create(['total' => 100]);
    Sale::factory()->create(['total' => 50]);
    Sale::factory()->cancelled()->create(['total' => 900]);
    Installment::factory()->dueIn(5)->create(['sale_id' => $a->id, 'amount' => 70]);
    $cancelled = Sale::cancelled()->first();
    Installment::factory()->dueIn(5)->create(['sale_id' => $cancelled->id, 'amount' => 500]);

    $query = new SalesQuery;
    $summary = $query->summary($query->filtered('today', null, false, ''));

    expect($summary['count'])->toBe(2)
        ->and($summary['cancelled'])->toBe(1)
        ->and($summary['revenue']->amount())->toBe(15000)
        ->and($summary['average']->amount())->toBe(7500)
        ->and($summary['receivable']->amount())->toBe(7000);
});
