<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Services\CustomerSearch;
use App\Services\Sales\SaleData;
use App\Services\Sales\SaleRegistrar;
use App\Support\Money;
use Database\Seeders\LookupSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    StoreSetting::current()->update(['pix_discount_percent' => 5]);
    $this->category = Category::factory()->create(['name' => 'Colares']);
    $this->material = Material::where('slug', 'ouro-18k')->first();
    $this->colar = Product::factory()->create(['category_id' => $this->category->id, 'material_id' => $this->material->id, 'name' => 'Colar Ponto de Luz', 'price' => 100]);
    $this->argola = Product::factory()->create(['category_id' => $this->category->id, 'material_id' => $this->material->id, 'name' => 'Argola', 'price' => 50]);
    $this->registrar = app(SaleRegistrar::class);
});

function saleData(array $overrides = []): SaleData
{
    $args = $overrides + [
        'items' => [['product_id' => test()->colar->id, 'quantity' => 1]],
        'paymentSlug' => 'dinheiro',
        'channelSlug' => 'whatsapp',
    ];

    return new SaleData(...$args);
}

function expectInvalid(callable $fn, string $field): void
{
    try {
        $fn();
        test()->fail("Expected validation error on {$field}");
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey($field);
    }
}

it('requires at least one item', function () {
    expectInvalid(fn () => $this->registrar->register(saleData(['items' => []])), 'items');
    expectInvalid(fn () => $this->registrar->register(saleData(['items' => [['product_id' => $this->colar->id, 'quantity' => 0]]])), 'items');

    expect(Sale::count())->toBe(0);
});

it('freezes item prices and names at the time of the sale', function () {
    $sale = $this->registrar->register(saleData(['items' => [['product_id' => $this->colar->id, 'quantity' => 2], ['product_id' => $this->argola->id, 'quantity' => 1]]]));

    $this->colar->update(['price' => 999, 'name' => 'Renomeado']);

    $item = $sale->fresh()->items->firstWhere('product_name', 'Colar Ponto de Luz');
    expect($item->unit_price)->toBe('100.00')
        ->and($item->line_total)->toBe('200.00')
        ->and($item->quantity)->toBe(2)
        ->and($item->category_name)->toBe('Colares')
        ->and($item->material_name)->toBe('Ouro 18k')
        ->and($sale->subtotal)->toBe('250.00')
        ->and($sale->total)->toBe('250.00');
});

it('merges repeated products into one line', function () {
    $sale = $this->registrar->register(saleData(['items' => [['product_id' => $this->colar->id, 'quantity' => 1], ['product_id' => $this->colar->id, 'quantity' => 2]]]));

    expect($sale->items)->toHaveCount(1)->and($sale->items[0]->quantity)->toBe(3);
});

it('assigns sequential numbers', function () {
    $first = $this->registrar->register(saleData());
    $second = $this->registrar->register(saleData());

    expect($second->number)->toBe($first->number + 1);
});

it('applies the Pix discount automatically and only for Pix', function () {
    $pix = $this->registrar->register(saleData(['items' => [['product_id' => $this->colar->id, 'quantity' => 2]], 'paymentSlug' => 'pix']));
    $cash = $this->registrar->register(saleData(['items' => [['product_id' => $this->colar->id, 'quantity' => 2]], 'paymentSlug' => 'dinheiro']));
    $debit = $this->registrar->register(saleData(['items' => [['product_id' => $this->colar->id, 'quantity' => 2]], 'paymentSlug' => 'debito']));

    expect($pix->pix_discount)->toBe('10.00')->and($pix->total)->toBe('190.00')
        ->and($cash->pix_discount)->toBe('0.00')->and($cash->total)->toBe('200.00')
        ->and($debit->pix_discount)->toBe('0.00');
});

it('adds the other discount to the Pix discount and caps the total discount at the subtotal', function () {
    $sale = $this->registrar->register(saleData(['paymentSlug' => 'pix', 'otherDiscount' => Money::from('10')]));

    expect($sale->pix_discount)->toBe('5.00')->and($sale->other_discount)->toBe('10.00')->and($sale->total)->toBe('85.00');

    $capped = $this->registrar->register(saleData(['paymentSlug' => 'pix', 'otherDiscount' => Money::from('500')]));

    expect($capped->pix_discount)->toBe('5.00')->and($capped->other_discount)->toBe('95.00')->and($capped->total)->toBe('0.00');
});

it('enforces installment limits per payment method', function (string $slug, int $installments, bool $valid) {
    $customer = Customer::factory()->create();
    $call = fn () => $this->registrar->register(saleData(['paymentSlug' => $slug, 'installments' => $installments, 'customerId' => $customer->id]));

    $valid ? expect($call()->installments_count)->toBe($installments) : expectInvalid($call, 'installments');
})->with([
    ['credito', 1, true], ['credito', 6, true], ['credito', 7, false],
    ['debito', 2, false], ['dinheiro', 3, false], ['pix', 2, false],
    ['crediario', 1, true], ['crediario', 6, true], ['crediario', 0, false],
]);

it('requires a customer for credit store sales', function () {
    expectInvalid(fn () => $this->registrar->register(saleData(['paymentSlug' => 'crediario', 'installments' => 2])), 'customer');

    expect(Sale::count())->toBe(0);
});

it('allows a sale without customer for other payment methods', function () {
    expect($this->registrar->register(saleData(['paymentSlug' => 'pix']))->customer_id)->toBeNull();
});

it('rejects future dates and accepts past dates keeping the registration time', function () {
    expectInvalid(fn () => $this->registrar->register(saleData(['date' => today()->addDay()])), 'date');

    Carbon::setTestNow('2026-10-19 15:30:00');
    $sale = $this->registrar->register(saleData(['date' => Carbon::parse('2026-10-15')]));

    expect($sale->sold_at->format('Y-m-d H:i'))->toBe('2026-10-15 15:30');
    Carbon::setTestNow();
});

it('generates monthly installments for the store credit', function () {
    Carbon::setTestNow('2026-10-19 10:00:00');
    $customer = Customer::factory()->create();
    $sale = $this->registrar->register(saleData([
        'items' => [['product_id' => $this->colar->id, 'quantity' => 1]],
        'paymentSlug' => 'crediario', 'installments' => 3, 'customerId' => $customer->id,
    ]));

    expect($sale->installments->pluck('amount')->all())->toBe(['33.33', '33.33', '33.34'])
        ->and($sale->installments->map(fn ($i) => $i->due_date->toDateString())->all())->toBe(['2026-11-19', '2026-12-19', '2027-01-19'])
        ->and($sale->first_due_date->toDateString())->toBe('2026-11-19');
    Carbon::setTestNow();
});

it('uses the chosen first due date for the store credit', function () {
    $customer = Customer::factory()->create();
    $sale = $this->registrar->register(saleData([
        'paymentSlug' => 'crediario', 'installments' => 2, 'customerId' => $customer->id, 'firstDueDate' => Carbon::parse('2027-02-10'),
    ]));

    expect($sale->installments->first()->due_date->toDateString())->toBe('2027-02-10');
});

it('does not create local installments for card credit', function () {
    $sale = $this->registrar->register(saleData(['paymentSlug' => 'credito', 'installments' => 3]));

    expect($sale->installments_count)->toBe(3)->and($sale->installments)->toHaveCount(0);
});

it('finds customers by the start of any word without accents or by 3+ phone digits, up to 6', function () {
    Customer::factory()->create(['name' => 'Mariana Costa', 'whatsapp' => '69999034417']);
    Customer::factory()->create(['name' => 'Gabriela Martins', 'whatsapp' => '69996542280']);
    Customer::factory()->create(['name' => 'Patrícia Gomes', 'whatsapp' => '69992315590']);
    Customer::factory()->create(['name' => 'Amanda Souza', 'whatsapp' => '69991110000']);

    $search = new CustomerSearch;

    expect($search->search('ma')->pluck('name')->all())->toBe(['Gabriela Martins', 'Mariana Costa'])
        ->and($search->search('patricia')->pluck('name')->all())->toBe(['Patrícia Gomes'])
        ->and($search->search('654')->pluck('name')->all())->toBe(['Gabriela Martins'])
        ->and($search->search('65')->pluck('name')->all())->toBe([]);

    Customer::factory()->count(8)->create(['name' => 'Maria Teste']);
    expect($search->search('maria'))->toHaveCount(6);
});
