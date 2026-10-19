<?php

namespace Database\Factories;

use App\Models\Installment;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Installment> */
class InstallmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'number' => 1,
            'amount' => 50,
            'due_date' => today()->addMonth(),
            'paid_at' => null,
        ];
    }

    public function overdue(int $days = 1): static
    {
        return $this->state(['due_date' => today()->subDays($days), 'paid_at' => null]);
    }

    public function paid(): static
    {
        return $this->state(['paid_at' => today()]);
    }

    public function dueIn(int $days): static
    {
        return $this->state(['due_date' => today()->addDays($days), 'paid_at' => null]);
    }
}
