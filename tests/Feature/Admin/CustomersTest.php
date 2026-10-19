<?php

use App\Livewire\Admin\Customers\Form;
use App\Livewire\Admin\Customers\Index;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Models\User;
use Database\Seeders\LookupSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    StoreSetting::current()->update(['city' => 'Ji-Paraná']);
    $this->actingAs(User::factory()->create());
});

it('creates a customer from the form and redirects to the profile', function () {
    Livewire::test(Form::class)
        ->set('name', 'Ana Paula')->set('whatsapp', '(69) 99812-4431')->set('birthday', '12/03')
        ->call('save')->assertHasNoErrors();

    $customer = Customer::first();
    expect($customer->whatsapp)->toBe('69998124431')->and($customer->birth_month)->toBe(3)->and($customer->city)->toBe('Ji-Paraná');
});

it('shows validation errors in the form', function () {
    Livewire::test(Form::class)->set('name', '')->call('save')->assertHasErrors('name');
    Livewire::test(Form::class)->set('name', 'Ana')->set('whatsapp', '123')->call('save')->assertHasErrors('whatsapp');
    Livewire::test(Form::class)->set('name', 'Ana')->set('birthday', '99/99')->call('save')->assertHasErrors('birthday');
});

it('blocks a duplicated whatsapp', function () {
    Customer::factory()->create(['whatsapp' => '69999990000']);

    Livewire::test(Form::class)->set('name', 'Outra')->set('whatsapp', '(69) 99999-0000')->call('save')->assertHasErrors('whatsapp');
});

it('searches customers by name ignoring accents and by phone digits', function () {
    Customer::factory()->create(['name' => 'Mariana Costa', 'whatsapp' => '69999034417']);
    Customer::factory()->create(['name' => 'Gabriela Martins', 'whatsapp' => '69996542280']);
    Customer::factory()->create(['name' => 'Patrícia Gomes', 'whatsapp' => '69992315590']);

    Livewire::test(Index::class)->set('search', 'patricia')->assertSee('Patrícia Gomes')->assertDontSee('Mariana Costa');
    Livewire::test(Index::class)->set('search', 'mar')->assertSee('Mariana Costa')->assertSee('Gabriela Martins')->assertDontSee('Patrícia');
    Livewire::test(Index::class)->set('search', '96542280')->assertSee('Gabriela Martins')->assertDontSee('Mariana Costa');
});

it('lists customers with overdue installments first', function () {
    $a = Customer::factory()->create(['name' => 'Ana']);
    $b = Customer::factory()->create(['name' => 'Bia']);
    $c = Customer::factory()->create(['name' => 'Carla']);
    $sale = Sale::factory()->create(['customer_id' => $c->id]);
    Installment::factory()->overdue(5)->create(['sale_id' => $sale->id]);

    Livewire::test(Index::class)->assertSeeInOrder(['Carla', 'Ana', 'Bia']);
});

it('filters customers with open installments', function () {
    $withDebt = Customer::factory()->create(['name' => 'Devedora']);
    Customer::factory()->create(['name' => 'Quitada']);
    $sale = Sale::factory()->create(['customer_id' => $withDebt->id]);
    Installment::factory()->dueIn(10)->create(['sale_id' => $sale->id]);

    Livewire::test(Index::class)->call('setFilter', 'open')->assertSee('Devedora')->assertDontSee('Quitada');
});

it('does not count cancelled sales as debt in the open filter', function () {
    $customer = Customer::factory()->create(['name' => 'Cancelou']);
    $sale = Sale::factory()->cancelled()->create(['customer_id' => $customer->id]);
    Installment::factory()->dueIn(10)->create(['sale_id' => $sale->id]);

    Livewire::test(Index::class)->call('setFilter', 'open')->assertDontSee('Cancelou');
});

it('shows the profile with totals and installments, and receives an installment', function () {
    $customer = Customer::factory()->create(['name' => 'Mariana Costa']);
    $sale = Sale::factory()->create(['customer_id' => $customer->id, 'total' => 90, 'installments_count' => 2]);
    $installment = Installment::factory()->dueIn(10)->create(['sale_id' => $sale->id, 'number' => 1, 'amount' => 45]);
    Installment::factory()->dueIn(40)->create(['sale_id' => $sale->id, 'number' => 2, 'amount' => 45]);

    $component = Livewire::test(Form::class, ['customer' => $customer])
        ->assertSee('R$ 90,00')->assertSee('Parcela 1 de 2')->assertSee('R$ 90,00');

    $component->call('togglePaid', $installment->id);

    expect($installment->fresh()->paid_at)->not->toBeNull();
    $component->assertSee('Desfazer');
});

it('does not count cancelled sales in the profile', function () {
    $customer = Customer::factory()->create();
    Sale::factory()->create(['customer_id' => $customer->id, 'total' => 100]);
    Sale::factory()->cancelled()->create(['customer_id' => $customer->id, 'total' => 900]);

    Livewire::test(Form::class, ['customer' => $customer])->assertSee('R$ 100,00')->assertDontSee('R$ 900,00')->assertDontSee('R$ 1.000,00');
});

it('refuses to delete a customer with purchases and deletes one without', function () {
    $withSale = Customer::factory()->create();
    Sale::factory()->create(['customer_id' => $withSale->id]);
    $without = Customer::factory()->create();

    Livewire::test(Form::class, ['customer' => $withSale])->call('delete')->assertSet('confirmingDelete', true);
    expect(Customer::find($withSale->id))->not->toBeNull();

    Livewire::test(Form::class, ['customer' => $without])->call('delete')->assertRedirect(route('admin.customers'));
    expect(Customer::find($without->id))->toBeNull();
});

it('refuses to delete a customer whose only sale is cancelled', function () {
    $customer = Customer::factory()->create();
    Sale::factory()->cancelled()->create(['customer_id' => $customer->id]);

    Livewire::test(Form::class, ['customer' => $customer])->call('delete');

    expect(Customer::find($customer->id))->not->toBeNull();
});
