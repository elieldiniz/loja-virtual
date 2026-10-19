<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SaleNumberGenerator
{
    public function next(): int
    {
        return (int) DB::selectOne("SELECT nextval('sales_number_seq') AS number")->number;
    }

    public static function format(int $number): string
    {
        return '#'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
