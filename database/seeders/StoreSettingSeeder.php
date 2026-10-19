<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (StoreSetting::query()->exists()) {
            return;
        }

        $setting = StoreSetting::create([
            'city' => 'Ji-Paraná',
            'state' => 'RO',
            'pix_discount_percent' => 5,
            'max_installments' => 3,
            'welcome_message' => 'Oi! Quer ajuda pra escolher? Fala com a gente 😊',
            'help_invite_enabled' => true,
            'help_invite_minutes' => 2,
            'birthday_gift_percent' => 10,
            'banner_enabled' => true,
        ]);

        foreach ([
            'Atendimento de verdade pelo WhatsApp',
            '5% de desconto no Pix · Até 3x sem juros',
            'Dúvidas? Fale com a gente, atendimento de verdade',
        ] as $position => $phrase) {
            $setting->promoPhrases()->create(['phrase' => $phrase, 'position' => $position + 1]);
        }

        foreach ([
            ['label' => 'Ajuda para escolher um presente', 'message' => null, 'gift' => true],
            ['label' => 'Entrega e envio para outras cidades', 'message' => 'Oi! Como funciona a entrega? Vocês enviam para a minha cidade?', 'gift' => false],
            ['label' => 'Outra dúvida', 'message' => 'Oi! Tenho uma dúvida:', 'gift' => false],
        ] as $position => $shortcut) {
            $setting->chatShortcuts()->create([
                'label' => $shortcut['label'],
                'whatsapp_message' => $shortcut['message'],
                'opens_gift_section' => $shortcut['gift'],
                'position' => $position + 1,
            ]);
        }
    }
}
