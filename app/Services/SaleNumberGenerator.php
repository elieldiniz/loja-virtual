<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SaleNumberGenerator
{
    /**
     * Próximo número de venda. O contador é travado até o fim da transação, então vendas
     * registradas ao mesmo tempo nunca recebem o mesmo número e um registro desfeito não deixa buraco.
     */
    public function next(): int
    {
        return DB::transaction(function () {
            $current = (int) (DB::table('counters')->where('name', 'sales')->lockForUpdate()->value('value') ?? 0);
            $next = $current + 1;

            DB::table('counters')->updateOrInsert(['name' => 'sales'], ['value' => $next]);

            return $next;
        });
    }

    public static function format(int $number): string
    {
        return '#'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
