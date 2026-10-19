<?php

use App\Livewire\Admin\Products\Form;
use App\Livewire\Admin\Products\Index;
use App\Models\Category;
use App\Models\Material;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Database\Seeders\LookupSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    $this->seed(LookupSeeder::class);
    $this->actingAs(User::factory()->create());
    $this->category = Category::factory()->create(['name' => 'Colares']);
    $this->material = Material::where('slug', 'ouro-18k')->first();
});

function fillForm($component, array $overrides = [])
{
    $data = $overrides + ['name' => 'Colar Ponto de Luz', 'price' => '99,90', 'oldPrice' => '', 'categoryId' => (string) test()->category->id, 'materialId' => (string) test()->material->id];

    foreach ($data as $key => $value) {
        $component->set($key, $value);
    }

    return $component;
}

it('creates a product with the required fields', function () {
    fillForm(Livewire::test(Form::class))->call('save')->assertRedirect(route('admin.products'));

    $product = Product::first();
    expect($product->name)->toBe('Colar Ponto de Luz')
        ->and($product->price)->toBe('99.90')
        ->and($product->old_price)->toBeNull()
        ->and($product->is_active)->toBeFalse();
});

it('does not save without name or price and flags the fields', function () {
    $component = fillForm(Livewire::test(Form::class), ['name' => '', 'price' => ''])->call('save');

    expect(Product::count())->toBe(0)
        ->and($component->get('errorsBag'))->toHaveKeys(['name', 'price']);
});

it('rejects a price of zero', function () {
    $component = fillForm(Livewire::test(Form::class), ['price' => '0,00'])->call('save');

    expect(Product::count())->toBe(0)->and($component->get('errorsBag'))->toHaveKey('price');
});

it('zeroes the old price when it is not greater than the price', function (string $old, ?string $expected) {
    fillForm(Livewire::test(Form::class), ['oldPrice' => $old])->call('save');

    expect(Product::first()->old_price)->toBe($expected);
})->with([
    'equal' => ['99,90', null],
    'lower' => ['50,00', null],
    'higher' => ['139,90', '139.90'],
]);

it('requires at least one photo to show the product on the site', function () {
    $component = fillForm(Livewire::test(Form::class), ['isActive' => true])->call('save');

    expect(Product::count())->toBe(0)->and($component->get('errorsBag'))->toHaveKey('photos');
});

it('uploads photos, keeps their order and exposes cover and hover', function () {
    $component = fillForm(Livewire::test(Form::class), ['isActive' => true]);
    $component->set('newFiles', [UploadedFile::fake()->image('a.jpg', 600, 600), UploadedFile::fake()->image('b.png', 600, 600)]);
    $component->call('movePhoto', 1, -1)->call('save');

    $product = Product::first();
    $media = $product->getMedia(Product::PHOTOS)->sortBy('order_column')->values();

    expect($media)->toHaveCount(2)
        ->and($media[0]->name)->toBe('b')
        ->and($product->cover()->id)->toBe($media[0]->id)
        ->and($product->hoverPhoto()->id)->toBe($media[1]->id)
        ->and(Product::visible()->count())->toBe(1);
});

it('rejects files that are not images', function () {
    $component = Livewire::test(Form::class)->set('newFiles', [UploadedFile::fake()->create('arquivo.pdf', 100, 'application/pdf')]);

    $component->assertHasErrors('newFiles.0');
    expect($component->get('photoOrder'))->toBe([]);
});

it('removes a stored photo when saving', function () {
    $product = Product::factory()->active()->withPhotos(2)->create(['category_id' => $this->category->id, 'material_id' => $this->material->id]);

    $component = Livewire::test(Form::class, ['product' => $product]);
    $component->call('removePhoto', 0)->call('save');

    expect($product->fresh()->getMedia(Product::PHOTOS))->toHaveCount(1);
});

it('edits an existing product', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id, 'material_id' => $this->material->id, 'name' => 'Antigo']);

    Livewire::test(Form::class, ['product' => $product])->set('name', 'Novo nome')->set('price', '120,00')->call('save');

    expect($product->fresh()->name)->toBe('Novo nome')->and($product->fresh()->price)->toBe('120.00');
});

it('refuses to turn on a product without photos in the list toggle', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id, 'material_id' => $this->material->id]);

    Livewire::test(Index::class)->call('toggle', $product->id);

    expect($product->fresh()->is_active)->toBeFalse();
});

it('toggles a product with photos on and off', function () {
    $product = Product::factory()->withPhotos()->create(['category_id' => $this->category->id, 'material_id' => $this->material->id]);

    Livewire::test(Index::class)->call('toggle', $product->id);
    expect($product->fresh()->is_active)->toBeTrue();

    Livewire::test(Index::class)->call('toggle', $product->id);
    expect($product->fresh()->is_active)->toBeFalse();
});

it('searches by name ignoring accents and filters by category', function () {
    $outra = Category::factory()->create();
    Product::factory()->create(['name' => 'Coração Vazado', 'category_id' => $this->category->id, 'material_id' => $this->material->id]);
    Product::factory()->create(['name' => 'Argola', 'category_id' => $outra->id, 'material_id' => $this->material->id]);

    Livewire::test(Index::class)->set('search', 'coracao')->assertSee('Coração Vazado')->assertDontSee('Argola');
    Livewire::test(Index::class)->set('categoryId', (string) $outra->id)->assertSee('Argola')->assertDontSee('Coração Vazado');
});

it('shows counters for products on the site and hidden', function () {
    Product::factory()->withPhotos()->active()->create(['category_id' => $this->category->id, 'material_id' => $this->material->id]);
    Product::factory()->count(2)->create(['category_id' => $this->category->id, 'material_id' => $this->material->id]);

    Livewire::test(Index::class)->assertSee('1 no site · 2 ocultos');
});

it('keeps the sale history when a sold product is deleted', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id, 'material_id' => $this->material->id, 'name' => 'Colar', 'price' => 99.90]);
    $sale = Sale::factory()->create();
    $item = SaleItem::factory()->create(['sale_id' => $sale->id, 'product_id' => $product->id, 'product_name' => 'Colar', 'unit_price' => 99.90, 'line_total' => 99.90]);

    Livewire::test(Index::class)->call('confirmDelete', $product->id)->call('delete');

    expect(Product::find($product->id))->toBeNull()
        ->and($item->fresh()->product_id)->toBeNull()
        ->and($item->fresh()->product_name)->toBe('Colar')
        ->and($item->fresh()->unit_price)->toBe('99.90');
});
