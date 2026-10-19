<?php

namespace App\Support;

class CategoryArt
{
    /** Escolhe a ilustração decorativa da categoria pelo nome (a mesma ideia dos mockups). */
    public static function key(?string $name): string
    {
        $n = SearchText::normalize($name);

        return match (true) {
            str_contains($n, 'brinco') => 'hoop',
            str_contains($n, 'colar') => 'point',
            str_contains($n, 'anel'), str_contains($n, 'aneis') => 'solitaire',
            str_contains($n, 'pulseira') => 'chain',
            str_contains($n, 'conjunto') => 'pearlset',
            str_contains($n, 'piercing') => 'piercing',
            default => 'point',
        };
    }
}
