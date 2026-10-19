<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Support\Money;

/** Totais de uma venda: desconto Pix automático + "outro desconto", limitados ao subtotal. */
class SaleCalculator
{
    /**
     * @return array{subtotal: Money, pix_discount: Money, other_discount: Money, total: Money}
     */
    public function totals(Money $subtotal, PaymentMethod $method, ?Money $otherDiscount, int $pixDiscountPercent): array
    {
        $pixDiscount = $method->applies_pix_discount
            ? $subtotal->subtract($subtotal->percent(100 - $pixDiscountPercent))
            : Money::zero();

        $remaining = $subtotal->subtract($pixDiscount);
        $other = ($otherDiscount === null || $otherDiscount->amount() < 0)
            ? Money::zero()
            : $otherDiscount->min($remaining);

        return [
            'subtotal' => $subtotal,
            'pix_discount' => $pixDiscount,
            'other_discount' => $other,
            'total' => $remaining->subtract($other),
        ];
    }
}
