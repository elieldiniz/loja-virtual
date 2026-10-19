<?php

namespace App\Services\Dashboard;

use Carbon\CarbonImmutable;

/** Período do Painel (7, 30 ou 90 dias contando hoje) e o período anterior de mesmo tamanho. */
final class Period
{
    public const ALLOWED = [7, 30, 90];

    public function __construct(
        public readonly int $days,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly CarbonImmutable $previousStart,
        public readonly CarbonImmutable $previousEnd,
    ) {}

    public static function last(int $days, ?CarbonImmutable $today = null): self
    {
        $days = in_array($days, self::ALLOWED, true) ? $days : 30;
        $today = ($today ?? CarbonImmutable::today())->startOfDay();

        return new self(
            days: $days,
            start: $today->subDays($days - 1),
            end: $today->endOfDay(),
            previousStart: $today->subDays(2 * $days - 1),
            previousEnd: $today->subDays($days)->endOfDay(),
        );
    }

    public function previous(): self
    {
        return new self($this->days, $this->previousStart, $this->previousEnd, $this->previousStart->subDays($this->days), $this->previousStart->subDay()->endOfDay());
    }
}
