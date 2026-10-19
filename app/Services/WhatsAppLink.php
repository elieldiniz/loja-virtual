<?php

namespace App\Services;

use App\Models\StoreSetting;
use App\Support\Phone;
use RuntimeException;

class WhatsAppLink
{
    public static function make(?string $phone, string $message): string
    {
        $number = Phone::forWhatsApp($phone);

        if ($number === '') {
            throw new RuntimeException('Número de WhatsApp não configurado.');
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }

    /** Link para o WhatsApp da loja (Loja → Contato). */
    public static function toStore(string $message, ?StoreSetting $setting = null): string
    {
        return self::make(($setting ?? StoreSetting::current())->whatsapp, $message);
    }
}
