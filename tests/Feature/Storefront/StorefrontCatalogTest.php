<?php

use App\Models\Category;
use App\Models\Material;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Services\PricingCalculator;
use App\Services\Storefront\StorefrontCatalog;
use App\Support\ProductCardData;
use Database\Seeders\LookupSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(LookupSeeder::class);
    $this->catalog = new StorefrontCatalog;
    $this->category = Category::factory()->create(['name' => 'Colares', 'position' => 2]);
    $this->gold = Material::where('slug', 'ouro-18k')->first();
    $this->silver = Material::where('slug', 'prata-925')->first();
});

function visibleProduct(array $attrs = [], int $photos = 1): Product
{
    return Product::factory()->active()->withPhotos($photos)->create($attrs + ['category_id' => test()->category->id, 'material_id' => test()->gold->id]);
}

it('lists categories in the order defined in the panel', function () {
    Category::factory()->create(['name' => 'Brincos', 'position' => 1]);
    Category::factory()->create(['name' => 'Anéis', 'position' => 3]);

    expect($this->catalog->categories()->pluck('name')->all())->toBe(['Brincos', 'Colares', 'Anéis']);
});

it('only exposes active products with at least one photo', function () {
    $ok = visibleProduct();
    Product::factory()->active()->create(['category_id' => $this->category->id, 'material_id' => $this->gold->id]);
    Product::factory()->withPhotos()->create(['category_id' => $this->category->id, 'material_id' => $this->gold->id]);

    expect($this->catalog->query()->pluck('id')->all())->toBe([$ok->id]);
});

it('limits new releases to 4 and filters best sellers', function () {
    foreach (range(1, 6) as $i) {
        visibleProduct(['is_new_release' => true]);
    }
    $best = visibleProduct(['is_best_seller' => true]);

    expect($this->catalog->newReleases())->toHaveCount(4)
        ->and($this->catalog->bestSellers()->pluck('id')->all())->toBe([$best->id]);
});

it('filters promotions by a valid old price', function () {
    $sale = visibleProduct(['price' => 100, 'old_price' => 140]);
    visibleProduct(['price' => 100, 'old_price' => null]);
    visibleProduct(['price' => 100, 'old_price' => 100]);

    expect($this->catalog->onSale()->pluck('id')->all())->toBe([$sale->id]);
});

it('filters by category and material', function () {
    $other = Category::factory()->create();
    $gold = visibleProduct();
    $silver = visibleProduct(['material_id' => $this->silver->id, 'category_id' => $other->id]);

    expect($this->catalog->inCategory($other->id)->pluck('id')->all())->toBe([$silver->id])
        ->and($this->catalog->inMaterial('ouro-18k')->pluck('id')->all())->toBe([$gold->id])
        ->and($this->catalog->inMaterial('prata-925')->pluck('id')->all())->toBe([$silver->id]);
});

it('searches by name and category ignoring accents and hides non visible products', function () {
    $aneis = Category::factory()->create(['name' => 'Anéis']);
    $a = visibleProduct(['name' => 'Coração Vazado']);
    $b = visibleProduct(['name' => 'Solitário', 'category_id' => $aneis->id]);
    Product::factory()->active()->create(['name' => 'Coração Oculto', 'category_id' => $this->category->id, 'material_id' => $this->gold->id]);

    expect($this->catalog->search('coracao')->pluck('id')->all())->toBe([$a->id])
        ->and($this->catalog->search('aneis')->pluck('id')->all())->toBe([$b->id])
        ->and($this->catalog->search('xyz')->all())->toBe([])
        ->and($this->catalog->search('   ')->all())->toBe([]);
});

it('builds the card data with pix, installments and badge and without material', function () {
    StoreSetting::current()->update(['pix_discount_percent' => 5, 'max_installments' => 3]);
    $product = visibleProduct(['price' => 99.90, 'old_price' => null, 'is_new_release' => true]);
    $card = ProductCardData::fromProduct($this->catalog->byIds([$product->id])->first(), PricingCalculator::forStore());

    expect($card->price->format())->toBe('R$ 99,90')
        ->and($card->installments)->toBe(3)
        ->and($card->installmentAmount->format())->toBe('R$ 33,30')
        ->and($card->pixPrice->format())->toBe('R$ 94,91')
        ->and($card->badge)->toBe('Novo')
        ->and($card->oldPrice)->toBeNull()
        ->and($card->coverUrl)->not->toBeNull()
        ->and(json_encode($card->toArray()))->not->toContain('Ouro');
});

it('shows the discount badge and struck price for a valid old price', function () {
    $product = visibleProduct(['price' => 119.90, 'old_price' => 139.90]);
    $card = ProductCardData::fromProduct($this->catalog->byIds([$product->id])->first(), PricingCalculator::forStore());

    expect($card->badge)->toBe('−14%')->and($card->oldPrice->format())->toBe('R$ 139,90');
});

it('returns only visible products for the requested ids', function () {
    $a = visibleProduct();
    $hidden = Product::factory()->create(['category_id' => $this->category->id, 'material_id' => $this->gold->id]);

    expect($this->catalog->byIds([$a->id, $hidden->id, 99999])->pluck('id')->all())->toBe([$a->id]);
});
