<?php

namespace App\Support;

use Illuminate\Support\Str;

class SearchText
{
    /**
     * Expressão SQL usada nas buscas sem acento. No MySQL a collation utf8mb4_unicode_ci já ignora
     * acentos e maiúsculas em LIKE e ORDER BY, então a coluna é usada como está.
     */
    public static function sqlNormalized(string $column): string
    {
        return $column;
    }

    public static function normalize(?string $text): string
    {
        $ascii = Str::ascii(trim((string) $text));

        return Str::of($ascii)->lower()->replaceMatches('/\s+/', ' ')->toString();
    }
}
