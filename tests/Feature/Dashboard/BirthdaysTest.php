<?php

use App\Models\Customer;
use App\Services\Birthdays;
use Illuminate\Support\Carbon;

it('orders upcoming birthdays by day and then the ones that already passed', function () {
    $today = Carbon::parse('2026-10-15');
    Customer::factory()->withBirthday(8, 10)->create(['name' => 'Passou 8']);
    Customer::factory()->withBirthday(22, 10)->create(['name' => 'Em 7']);
    Customer::factory()->withBirthday(15, 10)->create(['name' => 'Hoje']);
    Customer::factory()->withBirthday(3, 10)->create(['name' => 'Passou 3']);
    Customer::factory()->withBirthday(16, 10)->create(['name' => 'Amanhã']);
    Customer::factory()->withBirthday(15, 11)->create(['name' => 'Outro mês']);
    Customer::factory()->create(['name' => 'Sem aniversário']);

    $names = (new Birthdays)->forMonth($today)->pluck('customer.name')->all();

    expect($names)->toBe(['Hoje', 'Amanhã', 'Em 7', 'Passou 3', 'Passou 8']);
});

it('labels the days remaining', function () {
    expect(Birthdays::label(0))->toBe('Hoje')
        ->and(Birthdays::label(1))->toBe('Em 1 dia')
        ->and(Birthdays::label(7))->toBe('Em 7 dias')
        ->and(Birthdays::label(-2))->toBe('Já passou');
});
