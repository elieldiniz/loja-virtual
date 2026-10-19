<?php

use App\Services\SaleNumberGenerator;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

it('seeds lookups idempotently', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(DB::table('materials')->count())->toBe(2)
        ->and(DB::table('sale_channels')->count())->toBe(3)
        ->and(DB::table('payment_methods')->count())->toBe(5)
        ->and(DB::table('store_settings')->count())->toBe(1);
});

it('flags each payment method according to the schema', function () {
    $this->seed(DatabaseSeeder::class);
    $methods = DB::table('payment_methods')->get()->keyBy('slug');

    expect((bool) $methods['pix']->applies_pix_discount)->toBeTrue()
        ->and((bool) $methods['pix']->allows_installments)->toBeFalse()
        ->and((bool) $methods['credito']->allows_installments)->toBeTrue()
        ->and((int) $methods['credito']->max_installments)->toBe(6)
        ->and((bool) $methods['credito']->generates_receivables)->toBeFalse()
        ->and((bool) $methods['crediario']->generates_receivables)->toBeTrue();
});

it('seeds store settings with the default values', function () {
    $this->seed(DatabaseSeeder::class);
    $settings = DB::table('store_settings')->first();

    expect($settings->pix_discount_percent)->toBe(5)
        ->and($settings->max_installments)->toBe(3)
        ->and((bool) $settings->help_invite_enabled)->toBeTrue()
        ->and($settings->help_invite_minutes)->toBe(2)
        ->and($settings->birthday_gift_percent)->toBe(10);
});

it('generates sequential sale numbers formatted with leading zeros', function () {
    $generator = app(SaleNumberGenerator::class);
    $first = $generator->next();
    $second = $generator->next();

    expect($second)->toBe($first + 1)
        ->and(SaleNumberGenerator::format(1))->toBe('#0001')
        ->and(SaleNumberGenerator::format(616))->toBe('#0616')
        ->and(SaleNumberGenerator::format(12345))->toBe('#12345');
});
