<?php

namespace Database\Factories;

use App\Models\PromoPhrase;
use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PromoPhrase> */
class PromoPhraseFactory extends Factory
{
    public function definition(): array
    {
        return ['store_setting_id' => StoreSetting::factory(), 'phrase' => fake()->sentence(4), 'position' => 0];
    }
}
