<?php

use App\Livewire\Admin\Customers\QuickCreate;
use App\Livewire\Admin\Sales\Create;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::factory()->create());
    $category = Category::factory()->create();
    $material = Material::first();
    $this->colar = Product::factory()->create(['category_id' => $category->id, 'material_id' => $material->id, 'name' => 'Colar Ponto de Luz', 'price' => 100]);
    $this->argola = Product::factory()->create(['category_id' => $category->id, 'material_id' => $material->id, 'name' => 'Argola Lisa', 'price' => 50]);
});

it('adds, increments and removes pieces in the cart', function () {
    $component = Livewire::test(Create::class)
        ->call('add', $this->colar->id)->call('add', $this->colar->id)->call('add', $this->argola->id);

    expect($component->get('cart'))->toBe([$this->colar->id => 2, $this->argola->id => 1]);

    $component->call('changeQuantity', $this->colar->id, -1);
    expect($component->get('cart')[$this->colar->id])->toBe(1);

    $component->call('changeQuantity', $this->colar->id, -1);
    expect($component->get('cart'))->not->toHaveKey($this->colar->id);
});

it('shows live totals with the automatic Pix discount and the extra discount', function () {
    Livewire::test(Create::class)
        ->call('add', $this->colar->id)->call('add', $this->colar->id)
        ->assertSee('Desconto Pix (5%)')->assertSee('− R$ 10,00')->assertSee('R$ 190,00')
        ->set('extraDiscount', '20,00')->assertSee('Outro desconto')->assertSee('R$ 170,00');
});

it('registers a sale, redirects to the detail and keeps nothing in the new form', function () {
    $component = Livewire::test(Create::class)
        ->call('add', $this->colar->id)->set('payment', 'dinheiro')->set('channel', 'loja-fisica')
        ->call('register');

    $sale = Sale::first();
    $component->assertRedirect(route('admin.sales.show', $sale));

    expect($sale->items)->toHaveCount(1)->and($sale->total)->toBe('100.00')->and($sale->channel->slug)->toBe('loja-fisica');
});

it('does not register an empty sale', function () {
    Livewire::test(Create::class)->call('register')->assertHasErrors('items');

    expect(Sale::count())->toBe(0);
});

it('warns when store credit has no customer and asks to focus the field', function () {
    Livewire::test(Create::class)
        ->call('add', $this->colar->id)->set('payment', 'crediario')
        ->call('register')->assertHasErrors('customer')->assertDispatched('focus-customer');

    expect(Sale::count())->toBe(0);
});

it('defaults to 2 installments when switching to store credit and back to cash-like methods', function () {
    $component = Livewire::test(Create::class)->set('payment', 'crediario');
    expect($component->get('installments'))->toBe(2);

    $component->set('payment', 'pix');
    expect($component->get('installments'))->toBe(1);
});

it('creates installments for store credit and selects the customer from the search', function () {
    $customer = Customer::factory()->create(['name' => 'Mariana Costa']);

    $component = Livewire::test(Create::class)
        ->set('customerQuery', 'mar')->assertSee('Mariana Costa')
        ->call('pickCustomer', $customer->id)
        ->call('add', $this->colar->id)->set('payment', 'crediario')->set('installments', 3)
        ->call('register');

    $sale = Sale::first();
    expect($sale->customer_id)->toBe($customer->id)->and($sale->installments)->toHaveCount(3);
    $component->assertRedirect(route('admin.sales.show', $sale));
});

it('quick registers a customer and selects her in the sale', function () {
    $component = Livewire::test(Create::class)->set('customerQuery', 'Nova Cliente')->call('openQuick')->assertSet('showQuick', true);

    Livewire::test(QuickCreate::class, ['initialName' => 'Nova Cliente'])
        ->assertSet('name', 'Nova Cliente')
        ->call('save')->assertDispatched('customer-created');

    $customer = Customer::where('name', 'Nova Cliente')->first();
    expect($customer)->not->toBeNull();

    $component->dispatch('customer-created', id: $customer->id)->assertSet('customerId', $customer->id)->assertSet('showQuick', false);
});

it('rejects a future date', function () {
    Livewire::test(Create::class)
        ->call('add', $this->colar->id)->set('date', today()->addDay()->toDateString())->call('register')->assertHasErrors('date');
});

it('preselects the customer from the query string', function () {
    $customer = Customer::factory()->create(['name' => 'Ana Paula']);

    Livewire::withQueryParams(['cliente' => $customer->id])->test(Create::class)->assertSet('customerId', $customer->id)->assertSee('Ana Paula');
});
