<?php

use App\Models\Customer;
use App\Models\Installment;
use App\Models\Sale;
use App\Services\CustomerSummary;
use App\Services\InstallmentService;
use App\Services\Receivables;

it('receives an installment today and undoes it', function () {
    $installment = Installment::factory()->create();

    $service = new InstallmentService;
    $service->receive($installment);

    expect($installment->fresh()->paid_at->toDateString())->toBe(today()->toDateString());

    $service->undo($installment);

    expect($installment->fresh()->paid_at)->toBeNull();
});

it('cannot receive an installment of a cancelled sale', function () {
    $sale = Sale::factory()->cancelled()->create();
    $installment = Installment::factory()->create(['sale_id' => $sale->id]);

    expect(fn () => (new InstallmentService)->receive($installment))->toThrow(DomainException::class);
    expect($installment->fresh()->paid_at)->toBeNull();
});

it('summarizes a customer ignoring cancelled sales', function () {
    $customer = Customer::factory()->create();
    $active = Sale::factory()->create(['customer_id' => $customer->id, 'total' => 100, 'sold_at' => now()->subDays(2)]);
    Sale::factory()->create(['customer_id' => $customer->id, 'total' => 50, 'sold_at' => now()->subDay()]);
    $cancelled = Sale::factory()->cancelled()->create(['customer_id' => $customer->id, 'total' => 900]);

    Installment::factory()->create(['sale_id' => $active->id, 'number' => 1, 'amount' => 40, 'due_date' => today()->subDays(3)]);
    Installment::factory()->create(['sale_id' => $active->id, 'number' => 2, 'amount' => 30, 'due_date' => today()->addMonth()]);
    Installment::factory()->paid()->create(['sale_id' => $active->id, 'number' => 3, 'amount' => 30, 'due_date' => today()->subMonth()]);
    Installment::factory()->create(['sale_id' => $cancelled->id, 'number' => 1, 'amount' => 500, 'due_date' => today()->subDays(10)]);

    $summary = CustomerSummary::for($customer);

    expect($summary->total->amount())->toBe(15000)
        ->and($summary->purchases)->toBe(2)
        ->and($summary->average->amount())->toBe(7500)
        ->and($summary->openBalance->amount())->toBe(7000)
        ->and($summary->overdueCount)->toBe(1)
        ->and($summary->lastPurchaseAt->isSameDay(now()->subDay()))->toBeTrue();
});

it('returns an empty summary for a customer without purchases', function () {
    $summary = CustomerSummary::for(Customer::factory()->create());

    expect($summary->total->amount())->toBe(0)
        ->and($summary->purchases)->toBe(0)
        ->and($summary->average->amount())->toBe(0)
        ->and($summary->openBalance->amount())->toBe(0)
        ->and($summary->lastPurchaseAt)->toBeNull();
});

it('sums receivables from open installments of active sales only', function () {
    $active = Sale::factory()->create();
    $cancelled = Sale::factory()->cancelled()->create();

    Installment::factory()->create(['sale_id' => $active->id, 'number' => 1, 'amount' => 40, 'due_date' => today()->subDay()]);
    Installment::factory()->create(['sale_id' => $active->id, 'number' => 2, 'amount' => 60, 'due_date' => today()->addMonth()]);
    Installment::factory()->paid()->create(['sale_id' => $active->id, 'number' => 3, 'amount' => 25]);
    Installment::factory()->create(['sale_id' => $cancelled->id, 'number' => 1, 'amount' => 999, 'due_date' => today()->subDay()]);

    $receivables = new Receivables;

    expect($receivables->openTotal()->amount())->toBe(10000)
        ->and($receivables->openCount())->toBe(2)
        ->and($receivables->overdueTotal()->amount())->toBe(4000)
        ->and($receivables->overdueCount())->toBe(1)
        ->and($receivables->next(5)->pluck('amount')->map(fn ($a) => (float) $a)->all())->toBe([40.0, 60.0]);
});

it('limits and orders the next installments by due date', function () {
    $sale = Sale::factory()->create();
    foreach (range(1, 6) as $n) {
        Installment::factory()->create(['sale_id' => $sale->id, 'number' => $n, 'amount' => 10, 'due_date' => today()->addDays(7 - $n)]);
    }

    $next = (new Receivables)->next(5);

    expect($next)->toHaveCount(5)
        ->and($next->first()->number)->toBe(6);
});
