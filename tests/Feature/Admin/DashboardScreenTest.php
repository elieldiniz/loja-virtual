<?php

use App\Livewire\Admin\Dashboard;
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

it('renders the empty panel without errors', function () {
    $this->get(route('admin.dashboard'))
        ->assertOk()->assertSee('Painel')->assertSee('Nenhuma venda neste período.')->assertSee('sem dados do período anterior')
        ->assertSee('Nenhuma cliente faz aniversário este mês.');
});

it('shows indicators with the change versus the previous period', function () {
    $sale = Sale::factory()->create(['total' => 300, 'subtotal' => 300, 'sold_at' => now()]);
    SaleItem::factory()->create(['sale_id' => $sale->id, 'product_name' => 'Colar', 'quantity' => 2, 'unit_price' => 150, 'line_total' => 300]);
    $previous = Sale::factory()->create(['total' => 200, 'subtotal' => 200, 'sold_at' => now()->subDays(35)]);
    SaleItem::factory()->create(['sale_id' => $previous->id, 'product_name' => 'Argola', 'quantity' => 1, 'unit_price' => 200, 'line_total' => 200]);

    Livewire::test(Dashboard::class)
        ->assertSee('R$ 300')->assertSee('▲ +50%')->assertSee('vs. 30 dias antes')
        ->assertSee('Colar')->assertSee('Últimas vendas');
});

it('switches the period and the chart metric', function () {
    Livewire::test(Dashboard::class)
        ->call('setRange', 7)->assertSet('range', 7)->assertSee('últimos 7 dias')
        ->call('setRange', 15)->assertSet('range', 30)
        ->call('setMetric', 'pieces')->assertSet('metric', 'pieces')->assertSee('Peças vendidas')
        ->call('setMetric', 'invalid')->assertSet('metric', 'revenue');
});

it('shows the series as a table on demand', function () {
    Livewire::test(Dashboard::class)->assertDontSee('Faturamento</th>', false)->call('toggleTable')->assertSee('Faturamento')->assertSee('Esconder tabela');
});

it('lists receivables with the next installments and receives one', function () {
    $customer = Customer::factory()->create(['name' => 'Mariana Costa']);
    $sale = Sale::factory()->create(['customer_id' => $customer->id, 'installments_count' => 2]);
    $a = Installment::factory()->overdue(3)->create(['sale_id' => $sale->id, 'number' => 1, 'amount' => 40]);
    Installment::factory()->dueIn(20)->create(['sale_id' => $sale->id, 'number' => 2, 'amount' => 60]);

    $component = Livewire::test(Dashboard::class)
        ->assertSee('R$ 100,00')->assertSee('Mariana Costa')->assertSee('Vencida');

    $component->call('togglePaid', $a->id);

    expect($a->fresh()->paid_at)->not->toBeNull();
    $component->assertSee('R$ 60,00');
});

it('shows this month birthdays with the congratulation link', function () {
    Customer::factory()->withBirthday(today()->day, today()->month)->create(['name' => 'Aniversariante Hoje', 'whatsapp' => '69999990000']);

    Livewire::test(Dashboard::class)->assertSee('Aniversariantes de')->assertSee('Aniversariante Hoje')->assertSee('Hoje 🎉')->assertSee('Parabéns');
});

it('does not count cancelled sales in the panel', function () {
    $sale = Sale::factory()->cancelled()->create(['total' => 777, 'subtotal' => 777, 'sold_at' => now()]);
    SaleItem::factory()->create(['sale_id' => $sale->id, 'product_name' => 'Cancelada', 'unit_price' => 777, 'line_total' => 777]);

    Livewire::test(Dashboard::class)->assertDontSee('R$ 777')->assertDontSee('Cancelada');
});
