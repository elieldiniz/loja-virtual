<?php

namespace App\Services;

use App\Models\Customer;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/** Aniversariantes do mês: primeiro as que ainda vão fazer (por dia), depois as que já passaram. */
class Birthdays
{
    /** @return Collection<int, array{customer: Customer, day: int, diff: int}> */
    public function forMonth(?CarbonInterface $today = null): Collection
    {
        $today ??= today();

        return Customer::query()
            ->where('birth_month', $today->month)
            ->whereNotNull('birth_day')
            ->get()
            ->map(fn (Customer $c) => ['customer' => $c, 'day' => $c->birth_day, 'diff' => $c->birth_day - $today->day])
            ->sortBy([
                fn ($a, $b) => ($a['diff'] < 0) <=> ($b['diff'] < 0),
                fn ($a, $b) => $a['day'] <=> $b['day'],
                fn ($a, $b) => $a['customer']->name <=> $b['customer']->name,
            ])->values();
    }

    /** "Hoje", "Em 3 dias" ou "Já passou". */
    public static function label(int $diff): string
    {
        return match (true) {
            $diff === 0 => 'Hoje',
            $diff > 0 => 'Em '.$diff.' '.($diff === 1 ? 'dia' : 'dias'),
            default => 'Já passou',
        };
    }
}
