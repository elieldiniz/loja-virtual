<?php

namespace App\Support;

use Illuminate\Support\Str;

class SearchText
{
    private const ACCENTED = 'áàâãäåÁÀÂÃÄÅéèêëÉÈÊËíìîïÍÌÎÏóòôõöÓÒÔÕÖúùûüÚÙÛÜçÇñÑ';

    private const PLAIN = 'aaaaaaaaaaaaeeeeeeeeiiiiiiiioooooooooouuuuuuuuccnn';

    /** Expressão SQL (PostgreSQL) que normaliza uma coluna como {@see normalize()}: minúsculo e sem acentos. */
    public static function sqlNormalized(string $column): string
    {
        return "lower(translate({$column}, '".self::ACCENTED."', '".self::PLAIN."'))";
    }

    public static function normalize(?string $text): string
    {
        $ascii = Str::ascii(trim((string) $text));

        return Str::of($ascii)->lower()->replaceMatches('/\s+/', ' ')->toString();
    }
}
