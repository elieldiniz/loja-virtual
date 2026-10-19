<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'whatsapp' => '699'.fake()->unique()->numerify('########'),
            'city' => 'Ji-Paraná',
            'birth_day' => null,
            'birth_month' => null,
            'notes' => null,
        ];
    }

    public function withBirthday(int $day, int $month): static
    {
        return $this->state(['birth_day' => $day, 'birth_month' => $month]);
    }
}
