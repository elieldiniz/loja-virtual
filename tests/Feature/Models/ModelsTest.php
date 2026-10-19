<?php

use App\Enums\InstallmentStatus;
use App\Models\Category;
use App\Models\ChatShortcut;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\Material;
use App\Models\Product;
use App\Models\PromoPhrase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

it('normalizes the category name when saving', function () {
    $category = Category::create(['name' => 'Anéis']);

    expect($category->name_normalized)->toBe('aneis');
});

it('navigates product, category and material relations', function () {
    $product = Product::factory()->create();

    expect($product->category)->toBeInstanceOf(Category::class)
        ->and($product->material)->toBeInstanceOf(Material::class)
        ->and($product->category->products->pluck('id'))->toContain($product->id)
        ->and($product->material->products->pluck('id'))->toContain($product->id);
});

it('only lists products that are active and have at least one photo as visible', function () {
    $visible = Product::factory()->active()->withPhotos()->create();
    Product::factory()->active()->create();
    Product::factory()->withPhotos()->create();

    expect(Product::visible()->pluck('id')->all())->toBe([$visible->id]);
});

it('exposes cover and hover photos in order', function () {
    $product = Product::factory()->active()->withPhotos(2)->create();

    expect($product->cover())->not->toBeNull()
        ->and($product->hoverPhoto())->not->toBeNull()
        ->and($product->cover()->id)->not->toBe($product->hoverPhoto()->id);

    $single = Product::factory()->withPhotos(1)->create();
    expect($single->hoverPhoto())->toBeNull();
});

it('only treats the old price as valid when greater than the price', function () {
    expect(Product::factory()->make(['price' => 99.90, 'old_price' => 139.90])->hasValidOldPrice())->toBeTrue()
        ->and(Product::factory()->make(['price' => 99.90, 'old_price' => 99.90])->hasValidOldPrice())->toBeFalse()
        ->and(Product::factory()->make(['price' => 99.90, 'old_price' => null])->hasValidOldPrice())->toBeFalse();
});

it('wires sale relations in both directions', function () {
    $customer = Customer::factory()->create();
    $sale = Sale::factory()->create(['customer_id' => $customer->id]);
    $item = SaleItem::factory()->create(['sale_id' => $sale->id]);
    $installment = Installment::factory()->create(['sale_id' => $sale->id]);

    expect($sale->customer->is($customer))->toBeTrue()
        ->and($customer->sales->first()->is($sale))->toBeTrue()
        ->and($sale->items->first()->is($item))->toBeTrue()
        ->and($item->sale->is($sale))->toBeTrue()
        ->and($sale->installments->first()->is($installment))->toBeTrue()
        ->and($installment->sale->is($sale))->toBeTrue()
        ->and($sale->paymentMethod)->not->toBeNull()
        ->and($sale->channel)->not->toBeNull()
        ->and($customer->installments->first()->is($installment))->toBeTrue();
});

it('filters cancelled sales with the scopes', function () {
    $active = Sale::factory()->create();
    $cancelled = Sale::factory()->cancelled()->create();

    expect(Sale::notCancelled()->pluck('id')->all())->toBe([$active->id])
        ->and(Sale::cancelled()->pluck('id')->all())->toBe([$cancelled->id])
        ->and($cancelled->isCancelled())->toBeTrue();
});

it('derives the installment status from dates', function (callable $make, InstallmentStatus $expected) {
    expect($make()->status())->toBe($expected);
})->with([
    'paid' => [fn () => Installment::factory()->paid()->make(), InstallmentStatus::Paid],
    'overdue yesterday' => [fn () => Installment::factory()->overdue()->make(), InstallmentStatus::Overdue],
    'due today' => [fn () => Installment::factory()->dueIn(0)->make(), InstallmentStatus::DueSoon],
    'due in 7 days' => [fn () => Installment::factory()->dueIn(7)->make(), InstallmentStatus::DueSoon],
    'due in 8 days' => [fn () => Installment::factory()->dueIn(8)->make(), InstallmentStatus::Open],
]);

it('keeps a single cached store setting row and invalidates the cache on save', function () {
    $first = StoreSetting::current();
    $second = StoreSetting::current();

    expect(StoreSetting::count())->toBe(1)
        ->and($second->id)->toBe($first->id)
        ->and($first->pix_discount_percent)->toBe(5);

    $first->update(['pix_discount_percent' => 10]);

    expect(Cache::has(StoreSetting::CACHE_KEY))->toBeFalse()
        ->and(StoreSetting::current()->pix_discount_percent)->toBe(10);
});

it('orders promo phrases and shortcuts by position', function () {
    $setting = StoreSetting::current();
    PromoPhrase::factory()->create(['store_setting_id' => $setting->id, 'phrase' => 'B', 'position' => 2]);
    PromoPhrase::factory()->create(['store_setting_id' => $setting->id, 'phrase' => 'A', 'position' => 1]);
    ChatShortcut::factory()->create(['store_setting_id' => $setting->id, 'label' => 'Segundo', 'position' => 2]);
    ChatShortcut::factory()->create(['store_setting_id' => $setting->id, 'label' => 'Primeiro', 'position' => 1]);

    expect($setting->promoPhrases->pluck('phrase')->all())->toBe(['A', 'B'])
        ->and($setting->chatShortcuts->pluck('label')->all())->toBe(['Primeiro', 'Segundo']);
});
