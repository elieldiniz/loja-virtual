<?php

namespace App\Services\Storefront;

use App\Models\StoreSetting;
use App\Services\WhatsAppLink;
use RuntimeException;

/** Links de WhatsApp da vitrine; sem número configurado devolve "#" em vez de quebrar a página. */
class StorefrontLinks
{
    public function __construct(private readonly ?StoreSetting $setting = null) {}

    public function whatsapp(string $message): string
    {
        try {
            return WhatsAppLink::toStore($message, $this->setting);
        } catch (RuntimeException) {
            return '#';
        }
    }

    public function hasWhatsapp(): bool
    {
        return filled(($this->setting ?? StoreSetting::current())->whatsapp);
    }
}
