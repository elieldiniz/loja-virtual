<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SaleItem> */
class SaleItemFactory extends Factory
{
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 30, 300);

        return [
            'sale_id' => Sale::factory(),
            'product_id' => null,
            'category_id' => null,
            'product_name' => ucfirst(fake()->words(2, true)),
            'category_name' => 'Brincos',
            'material_name' => 'Ouro 18k',
            'unit_price' => $price,
            'quantity' => 1,
            'line_total' => $price,
        ];
    }
}
