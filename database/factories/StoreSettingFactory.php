<?php

namespace Database\Factories;

use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StoreSetting> */
class StoreSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'whatsapp' => '69999990000',
            'city' => 'Ji-Paraná',
            'state' => 'RO',
            'pix_discount_percent' => 5,
            'max_installments' => 3,
            'help_invite_enabled' => true,
            'help_invite_minutes' => 2,
            'birthday_gift_percent' => 10,
        ];
    }
}
