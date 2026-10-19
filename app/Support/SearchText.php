<?php

namespace App\Support;

use Illuminate\Support\Str;

class SearchText
{
    public static function normalize(?string $text): string
    {
        $ascii = Str::ascii(trim((string) $text));

        return Str::of($ascii)->lower()->replaceMatches('/\s+/', ' ')->toString();
    }
}
