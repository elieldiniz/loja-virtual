<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Sale> */
class SaleFactory extends Factory
{
    private static int $sequence = 100000;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 500);

        return [
            'number' => ++self::$sequence,
            'customer_id' => null,
            'payment_method_id' => fn () => PaymentMethod::query()->where('slug', 'pix')->value('id') ?? PaymentMethod::factory(),
            'sale_channel_id' => fn () => SaleChannel::query()->where('slug', 'whatsapp')->value('id') ?? SaleChannel::factory(),
            'installments_count' => 1,
            'first_due_date' => null,
            'subtotal' => $subtotal,
            'pix_discount' => 0,
            'other_discount' => 0,
            'total' => $subtotal,
            'sold_at' => now(),
            'cancelled_at' => null,
            'cancel_reason' => null,
        ];
    }

    public function cancelled(string $reason = 'Venda lançada por engano'): static
    {
        return $this->state(['cancelled_at' => now(), 'cancel_reason' => $reason]);
    }
}
