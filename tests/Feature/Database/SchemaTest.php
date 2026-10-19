<?php

use App\Services\SaleNumberGenerator;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function seedBasics(): void
{
    test()->seed(DatabaseSeeder::class);
}

function makeProduct(array $overrides = []): int
{
    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Brincos', 'name_normalized' => 'brincos-'.uniqid(), 'position' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    return DB::table('products')->insertGetId($overrides + [
        'category_id' => $categoryId,
        'material_id' => DB::table('materials')->value('id'),
        'name' => 'Argola', 'price' => 99.90,
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

function makeSale(?int $customerId = null): int
{
    return DB::table('sales')->insertGetId([
        'number' => app(SaleNumberGenerator::class)->next(),
        'customer_id' => $customerId,
        'payment_method_id' => DB::table('payment_methods')->value('id'),
        'sale_channel_id' => DB::table('sale_channels')->value('id'),
        'subtotal' => 100, 'total' => 100, 'sold_at' => now(),
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

it('creates every table from the schema', function () {
    foreach (['materials', 'payment_methods', 'sale_channels', 'users', 'categories', 'products', 'media',
        'customers', 'sales', 'sale_items', 'installments', 'store_settings', 'promo_phrases', 'chat_shortcuts'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("missing table {$table}");
    }
});

it('rejects duplicated lookup slugs', function () {
    seedBasics();

    expect(fn () => DB::table('materials')->insert(['name' => 'Outro', 'slug' => 'ouro-18k', 'created_at' => now(), 'updated_at' => now()]))
        ->toThrow(QueryException::class);
});

it('blocks deleting a category that still has products', function () {
    seedBasics();
    $productId = makeProduct();
    $categoryId = DB::table('products')->where('id', $productId)->value('category_id');

    expect(fn () => DB::table('categories')->where('id', $categoryId)->delete())->toThrow(QueryException::class);
});

it('rejects a duplicated normalized category name', function () {
    $row = ['name' => 'Anéis', 'name_normalized' => 'aneis', 'position' => 1, 'created_at' => now(), 'updated_at' => now()];
    DB::table('categories')->insert($row);

    expect(fn () => DB::table('categories')->insert($row))->toThrow(QueryException::class);
});

it('nulls sale item product when the product is deleted but keeps the item', function () {
    seedBasics();
    $productId = makeProduct();
    $saleId = makeSale();
    $itemId = DB::table('sale_items')->insertGetId([
        'sale_id' => $saleId, 'product_id' => $productId, 'product_name' => 'Argola',
        'unit_price' => 99.90, 'quantity' => 1, 'line_total' => 99.90,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('products')->where('id', $productId)->delete();

    $item = DB::table('sale_items')->find($itemId);
    expect($item->product_id)->toBeNull()
        ->and($item->product_name)->toBe('Argola');
});

it('blocks deleting a customer that has sales', function () {
    seedBasics();
    $customerId = DB::table('customers')->insertGetId(['name' => 'Mariana', 'created_at' => now(), 'updated_at' => now()]);
    makeSale($customerId);

    expect(fn () => DB::table('customers')->where('id', $customerId)->delete())->toThrow(QueryException::class);
});

it('rejects a duplicated installment number for the same sale', function () {
    seedBasics();
    $saleId = makeSale();
    $row = ['sale_id' => $saleId, 'number' => 1, 'amount' => 50, 'due_date' => now()->addMonth()->toDateString(), 'created_at' => now(), 'updated_at' => now()];
    DB::table('installments')->insert($row);

    expect(fn () => DB::table('installments')->insert($row))->toThrow(QueryException::class);
});

it('rejects a duplicated customer whatsapp', function () {
    $row = ['name' => 'Ana', 'whatsapp' => '69999990000', 'created_at' => now(), 'updated_at' => now()];
    DB::table('customers')->insert($row);

    expect(fn () => DB::table('customers')->insert($row))->toThrow(QueryException::class);
});
