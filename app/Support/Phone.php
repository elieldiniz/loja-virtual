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

    /** "(69) 99999-0000" a partir de dígitos (10 ou 11); devolve o texto original se não couber. */
    public static function format(?string $phone): string
    {
        $digits = self::normalize($phone);

        return match (strlen($digits)) {
            11 => sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7)),
            10 => sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6)),
            default => (string) $phone,
        };
    }

    /** Número no formato aceito pelo wa.me (com DDI 55 quando faltar). */
    public static function forWhatsApp(?string $phone): string
    {
        $digits = self::normalize($phone);

        return in_array(strlen($digits), [10, 11], true) ? '55'.$digits : $digits;
    }
}
