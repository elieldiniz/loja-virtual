<?php

namespace App\Support;

class Phone
{
    public const MIN_DIGITS = 10;

    public static function normalize(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    public static function isValid(?string $phone): bool
    {
        return strlen(self::normalize($phone)) >= self::MIN_DIGITS;
    }

    /** Número no formato aceito pelo wa.me (com DDI 55 quando faltar). */
    public static function forWhatsApp(?string $phone): string
    {
        $digits = self::normalize($phone);

        return in_array(strlen($digits), [10, 11], true) ? '55'.$digits : $digits;
    }
}
