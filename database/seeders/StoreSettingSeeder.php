<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('store_settings')->exists()) {
            return;
        }

        DB::table('store_settings')->insert([
            'city' => 'Ji-Paraná',
            'state' => 'RO',
            'pix_discount_percent' => 5,
            'max_installments' => 3,
            'help_invite_enabled' => true,
            'help_invite_minutes' => 2,
            'birthday_gift_percent' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
