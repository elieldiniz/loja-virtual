<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function settingsRow(array $overrides = []): array
{
    return $overrides + ['created_at' => now(), 'updated_at' => now()];
}

it('accepts valid limits and rejects values outside them', function (string $column, int $valid, int $invalid) {
    DB::table('store_settings')->insert(settingsRow([$column => $valid]));

    expect(fn () => DB::table('store_settings')->insert(settingsRow([$column => $invalid])))
        ->toThrow(QueryException::class);
})->with([
    'pix discount max' => ['pix_discount_percent', 30, 31],
    'pix discount min' => ['pix_discount_percent', 0, -1],
    'installments max' => ['max_installments', 12, 13],
    'installments min' => ['max_installments', 1, 0],
    'birthday gift max' => ['birthday_gift_percent', 50, 51],
    'help invite minutes' => ['help_invite_minutes', 5, 4],
]);

it('rejects non positive product prices', function () {
    $categoryId = DB::table('categories')->insertGetId(['name' => 'X', 'name_normalized' => 'x', 'created_at' => now(), 'updated_at' => now()]);
    $materialId = DB::table('materials')->insertGetId(['name' => 'Ouro', 'slug' => 'ouro', 'created_at' => now(), 'updated_at' => now()]);

    expect(fn () => DB::table('products')->insert([
        'category_id' => $categoryId, 'material_id' => $materialId, 'name' => 'Peça', 'price' => 0,
        'created_at' => now(), 'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});
