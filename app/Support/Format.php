<?php

namespace App\Support;

use Carbon\CarbonInterface;

class Format
{
    /** "Hoje, 10:30", "Ontem, 16:05" ou "12/10, 09:15". */
    public static function whenLabel(CarbonInterface $date): string
    {
        $days = (int) $date->copy()->startOfDay()->diffInDays(today(), false);
        $day = match ($days) {
            0 => 'Hoje',
            1 => 'Ontem',
            default => $date->format('d/m'),
        };

        return $day.', '.$date->format('H:i');
    }

    /** "Hoje, 19/10", "Ontem, 18/10" ou "Segunda, 12/10". */
    public static function dayTitle(CarbonInterface $date): string
    {
        $days = (int) $date->copy()->startOfDay()->diffInDays(today(), false);
        $names = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        $label = match ($days) {
            0 => 'Hoje',
            1 => 'Ontem',
            default => $names[$date->dayOfWeek],
        };

        return $label.', '.$date->format('d/m');
    }

    public static function pieces(int $count): string
    {
        return $count.' '.($count === 1 ? 'peça' : 'peças');
    }
}
