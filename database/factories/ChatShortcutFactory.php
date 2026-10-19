<?php

namespace Database\Factories;

use App\Models\ChatShortcut;
use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ChatShortcut> */
class ChatShortcutFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_setting_id' => StoreSetting::factory(),
            'label' => fake()->sentence(3),
            'whatsapp_message' => fake()->sentence(),
            'opens_gift_section' => false,
            'position' => 0,
        ];
    }
}
