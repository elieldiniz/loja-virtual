<?php

namespace App\Services;

use App\Support\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class InstallmentSplitter
{
    public const MAX_INSTALLMENTS = 6;

    /**
     * Divide o total em parcelas mensais; a última absorve a diferença de centavos.
     *
     * @return list<array{number: int, amount: Money, due_date: CarbonImmutable}>
     */
    public function split(Money $total, int $installments, CarbonInterface $firstDueDate): array
    {
        if ($installments < 1 || $installments > self::MAX_INSTALLMENTS) {
            throw new InvalidArgumentException('O crediário aceita de 1 a '.self::MAX_INSTALLMENTS.' parcelas.');
        }

        $base = $total->divideFloor($installments);
        $first = CarbonImmutable::instance($firstDueDate)->startOfDay();
        $parts = [];
        $allocated = Money::zero();

        for ($number = 1; $number <= $installments; $number++) {
            $amount = $number === $installments ? $total->subtract($allocated) : $base;
            $allocated = $allocated->add($amount);
            $parts[] = [
                'number' => $number,
                'amount' => $amount,
                'due_date' => $first->addMonthsNoOverflow($number - 1),
            ];
        }

        return $parts;
    }
}
