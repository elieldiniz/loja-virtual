<?php

namespace Database\Factories;

use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SaleChannel> */
class SaleChannelFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return ['name' => ucfirst($name), 'slug' => $name.'-'.fake()->unique()->numberBetween(1, 99999), 'is_active' => true];
    }
}
