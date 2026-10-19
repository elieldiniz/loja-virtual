<?php

use App\Livewire\Admin\Categories;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('creates categories at the end of the order', function () {
    Category::factory()->create(['name' => 'Brincos', 'position' => 1]);

    Livewire::test(Categories::class)->set('newName', 'Colares')->call('add');

    expect(Category::where('name', 'Colares')->first()->position)->toBe(2);
});

it('rejects empty and duplicated names ignoring case and accents', function () {
    Category::create(['name' => 'Anéis', 'position' => 1]);

    Livewire::test(Categories::class)->set('newName', '   ')->call('add')->assertSet('error', 'Informe o nome da categoria.');
    Livewire::test(Categories::class)->set('newName', 'ANEIS')->call('add')->assertSet('error', 'Já existe uma categoria com esse nome.');

    expect(Category::count())->toBe(1);
});

it('allows renaming a category keeping its own name', function () {
    $category = Category::create(['name' => 'Brincos', 'position' => 1]);

    Livewire::test(Categories::class)
        ->call('startEdit', $category->id)
        ->set('editName', 'BRINCOS')
        ->call('saveEdit')
        ->assertSet('editingId', null);

    expect($category->fresh()->name)->toBe('BRINCOS');
});

it('blocks renaming to another category name', function () {
    Category::create(['name' => 'Brincos', 'position' => 1]);
    $colares = Category::create(['name' => 'Colares', 'position' => 2]);

    Livewire::test(Categories::class)
        ->call('startEdit', $colares->id)->set('editName', 'brincos')->call('saveEdit')
        ->assertSet('error', 'Já existe uma categoria com esse nome.');
});

it('reorders categories and persists the position', function () {
    $a = Category::create(['name' => 'A', 'position' => 1]);
    $b = Category::create(['name' => 'B', 'position' => 2]);
    $c = Category::create(['name' => 'C', 'position' => 3]);

    Livewire::test(Categories::class)->call('move', $c->id, -1);

    expect(Category::query()->ordered()->pluck('name')->all())->toBe(['A', 'C', 'B']);

    Livewire::test(Categories::class)->call('move', $a->id, -1);

    expect(Category::query()->ordered()->pluck('name')->all())->toBe(['A', 'C', 'B']);
});

it('refuses to delete a category with products and reports how many', function () {
    $category = Category::factory()->create();
    Product::factory()->count(3)->create(['category_id' => $category->id]);

    Livewire::test(Categories::class)
        ->call('confirmDelete', $category->id)
        ->assertSee('Essa categoria tem 3 peças')
        ->call('delete');

    expect(Category::find($category->id))->not->toBeNull();
});

it('deletes an empty category after confirmation', function () {
    $category = Category::factory()->create();

    Livewire::test(Categories::class)->call('confirmDelete', $category->id)->call('delete');

    expect(Category::find($category->id))->toBeNull();
});
