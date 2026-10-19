<?php

use App\Livewire\Admin\Sales\Index;
use App\Livewire\Admin\Sales\Show;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Database\Seeders\LookupSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    $this->actingAs(User::factory()->create());
});

it('shows the empty state for today and totals excluding cancelled sales per day', function () {
    Livewire::test(Index::class)->assertSee('Nenhuma venda hoje ainda.');

    $a = Sale::factory()->create(['total' => 100, 'number' => 616]);
    SaleItem::factory()->create(['sale_id' => $a->id, 'product_name' => 'Colar']);
    $b = Sale::factory()->cancelled()->create(['total' => 900, 'number' => 617]);
    SaleItem::factory()->create(['sale_id' => $b->id, 'product_name' => 'Argola']);

    Livewire::test(Index::class)
        ->assertSee('#0616')->assertSee('#0617')->assertSee('Cancelada')
        ->assertSee('1 venda')->assertSee('R$ 100,00')
        ->assertSee('1 cancelada');
});

it('paginates 50 at a time with a show more button', function () {
    Sale::factory()->count(51)->create();

    $component = Livewire::test(Index::class);
    $component->assertSee('Mostrar mais 1 de 1 restantes');

    $component->call('showMore')->assertDontSee('Mostrar mais');
});

it('filters the list by period, payment and search', function () {
    $old = Sale::factory()->create(['sold_at' => now()->subDays(10), 'number' => 700]);
    SaleItem::factory()->create(['sale_id' => $old->id, 'product_name' => 'Peça Antiga']);

    Livewire::test(Index::class)->assertDontSee('Peça Antiga')
        ->call('setPeriod', '30')->assertSee('Peça Antiga')
        ->set('search', 'inexistente')->assertSee('Nenhuma venda encontrada com esses filtros.');
});

it('shows the detail with items, discounts, payment and installments', function () {
    $customer = Customer::factory()->create(['name' => 'Mariana Costa']);
    $sale = Sale::factory()->create(['customer_id' => $customer->id, 'number' => 616, 'subtotal' => 100, 'other_discount' => 10, 'total' => 90, 'installments_count' => 2]);
    SaleItem::factory()->create(['sale_id' => $sale->id, 'product_name' => 'Colar Ponto de Luz', 'quantity' => 1, 'unit_price' => 100, 'line_total' => 100]);
    Installment::factory()->dueIn(10)->create(['sale_id' => $sale->id, 'number' => 1, 'amount' => 45]);
    Installment::factory()->dueIn(40)->create(['sale_id' => $sale->id, 'number' => 2, 'amount' => 45]);

    Livewire::test(Show::class, ['sale' => $sale])
        ->assertSee('Venda #0616')->assertSee('Colar Ponto de Luz')->assertSee('− R$ 10,00')->assertSee('R$ 90,00')
        ->assertSee('Mariana Costa')->assertSee('Parcelas do crediário')->assertSee('Parcela 1 de 2')
        ->assertSee('Parcelado em 2x de R$ 45,00')->assertSee('Enviar recibo');
});

it('offers the receipt only with a customer whatsapp and for active sales', function () {
    $customer = Customer::factory()->create();
    $cancelled = Sale::factory()->cancelled()->create(['customer_id' => $customer->id]);
    $noPhone = Sale::factory()->create(['customer_id' => Customer::factory()->create(['whatsapp' => null])->id]);
    $anonymous = Sale::factory()->create();
    $ok = Sale::factory()->create(['customer_id' => $customer->id]);

    Livewire::test(Show::class, ['sale' => $cancelled])->assertDontSee('Enviar recibo');
    Livewire::test(Show::class, ['sale' => $noPhone])->assertDontSee('Enviar recibo');
    Livewire::test(Show::class, ['sale' => $anonymous])->assertDontSee('Enviar recibo');
    Livewire::test(Show::class, ['sale' => $ok])->assertSee('Enviar recibo');
});

it('requires a reason to cancel, then cancels and allows reactivation', function () {
    $sale = Sale::factory()->create();

    $component = Livewire::test(Show::class, ['sale' => $sale])
        ->call('askCancel')->set('reason', '   ')->call('cancel')->assertHasErrors('reason');

    expect($sale->fresh()->isCancelled())->toBeFalse();

    $component->set('reason', 'Cliente desistiu')->call('cancel')->assertSet('confirmingCancel', false)->assertSee('Cancelada em')->assertSee('Cliente desistiu');
    expect($sale->fresh()->isCancelled())->toBeTrue();

    $component->call('reactivate')->assertDontSee('Cancelada em');
    expect($sale->fresh()->isCancelled())->toBeFalse();
});

it('warns about the received amount when cancelling a credit sale', function () {
    $sale = Sale::factory()->create();
    Installment::factory()->paid()->create(['sale_id' => $sale->id, 'number' => 1, 'amount' => 33.33]);

    Livewire::test(Show::class, ['sale' => $sale])->call('askCancel')->assertSee('já foram recebidos R$ 33,33');
});

it('receives an installment from the sale detail', function () {
    $sale = Sale::factory()->create();
    $installment = Installment::factory()->dueIn(5)->create(['sale_id' => $sale->id, 'amount' => 50]);

    Livewire::test(Show::class, ['sale' => $sale])->call('togglePaid', $installment->id)->assertSee('Recebido');

    expect($installment->fresh()->paid_at)->not->toBeNull();
});
