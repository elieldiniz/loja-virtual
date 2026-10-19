<?php

use App\Services\PricingCalculator;
use App\Support\Money;

it('formats and converts money without floats', function () {
    expect(Money::from('99.90')->format())->toBe('R$ 99,90')
        ->and(Money::from(1234.56)->format())->toBe('R$ 1.234,56')
        ->and(Money::from('99.90')->toDecimal())->toBe('99.90')
        ->and(Money::cents(5)->toDecimal())->toBe('0.05')
        ->and(Money::from('10.00')->add(Money::from('0.10'))->amount())->toBe(1010)
        ->and(Money::from('33.30')->times(3)->format())->toBe('R$ 99,90')
        ->and(Money::from(null)->isZero())->toBeTrue();
});

it('rounds by cents', function () {
    expect(Money::from('100')->divideFloor(3)->amount())->toBe(3333)
        ->and(Money::from('100')->divideRound(3)->amount())->toBe(3333)
        ->and(Money::from('0.05')->divideRound(2)->amount())->toBe(3)
        ->and(Money::from('99.90')->percent(95)->format())->toBe('R$ 94,91')
        ->and(Money::from('279.70')->percent(95)->format())->toBe('R$ 265,72');
});

it('calculates Pix price, installments and old price rules', function () {
    $pricing = new PricingCalculator(pixDiscountPercent: 5, maxInstallments: 3);

    expect($pricing->pixPrice(Money::from('99.90'))->format())->toBe('R$ 94,91')
        ->and($pricing->installmentAmount(Money::from('99.90'))->format())->toBe('R$ 33,30')
        ->and($pricing->validOldPrice(Money::from('99.90'), Money::from('99.90')))->toBeNull()
        ->and($pricing->validOldPrice(Money::from('99.90'), Money::from('80')))->toBeNull()
        ->and($pricing->validOldPrice(Money::from('99.90'), null))->toBeNull()
        ->and($pricing->validOldPrice(Money::from('99.90'), Money::from('139.90'))?->format())->toBe('R$ 139,90');
});

it('keeps the price when the Pix discount is zero', function () {
    $pricing = new PricingCalculator(0, 3);

    expect($pricing->pixPrice(Money::from('99.90'))->format())->toBe('R$ 99,90');
});

it('shows discount badge or "Novo" for releases', function () {
    $pricing = new PricingCalculator(5, 3);

    expect($pricing->discountPercent(Money::from('119.90'), Money::from('139.90')))->toBe(14)
        ->and($pricing->badge(Money::from('119.90'), Money::from('139.90'), false))->toBe('−14%')
        ->and($pricing->badge(Money::from('119.90'), Money::from('139.90'), true))->toBe('−14%')
        ->and($pricing->badge(Money::from('99.90'), null, true))->toBe('Novo')
        ->and($pricing->badge(Money::from('99.90'), Money::from('99.90'), false))->toBeNull();
});

it('parses user typed money', function (?string $input, ?string $expected) {
    expect(Money::parse($input)?->toDecimal())->toBe($expected);
})->with([
    ['99,90', '99.90'],
    ['1.234,56', '1234.56'],
    ['R$ 99,90', '99.90'],
    ['99.90', '99.90'],
    ['1.234', '1234.00'],
    ['150', '150.00'],
    ['', null],
    [null, null],
    ['abc', null],
]);
