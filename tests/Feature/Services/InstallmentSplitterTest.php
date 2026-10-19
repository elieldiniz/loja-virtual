<?php

use App\Services\InstallmentSplitter;
use App\Support\Money;
use Illuminate\Support\Carbon;

it('splits R$ 100 in 3x giving the remainder to the last installment', function () {
    $parts = (new InstallmentSplitter)->split(Money::from('100'), 3, Carbon::parse('2026-11-19'));

    expect(array_map(fn ($p) => $p['amount']->format(), $parts))->toBe(['R$ 33,33', 'R$ 33,33', 'R$ 33,34'])
        ->and(array_map(fn ($p) => $p['due_date']->toDateString(), $parts))->toBe(['2026-11-19', '2026-12-19', '2027-01-19']);
});

it('always sums to the total', function (string $total, int $n) {
    $parts = (new InstallmentSplitter)->split(Money::from($total), $n, Carbon::parse('2026-01-10'));

    $sum = array_reduce($parts, fn (Money $carry, $p) => $carry->add($p['amount']), Money::zero());

    expect($sum->equals(Money::from($total)))->toBeTrue()->and($parts)->toHaveCount($n);
})->with([['99.99', 2], ['0.10', 3], ['1234.57', 6], ['50', 1]]);

it('rejects installment counts outside 1 to 6', function (int $n) {
    expect(fn () => (new InstallmentSplitter)->split(Money::from('100'), $n, now()))->toThrow(InvalidArgumentException::class);
})->with([0, 7]);

it('clamps month ends instead of overflowing', function () {
    $parts = (new InstallmentSplitter)->split(Money::from('100'), 3, Carbon::parse('2027-01-31'));

    expect(array_map(fn ($p) => $p['due_date']->toDateString(), $parts))->toBe(['2027-01-31', '2027-02-28', '2027-03-31']);
});
