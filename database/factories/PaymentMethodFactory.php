<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentMethod> */
class PaymentMethodFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => $name.'-'.fake()->unique()->numberBetween(1, 99999),
            'allows_installments' => false,
            'max_installments' => 1,
            'applies_pix_discount' => false,
            'generates_receivables' => false,
            'is_active' => true,
        ];
    }
}
