<?php

namespace App\Services;

use App\Models\StoreSetting;
use App\Support\Money;

class PricingCalculator
{
    public function __construct(
        public readonly int $pixDiscountPercent,
        public readonly int $maxInstallments,
    ) {}

    public static function forStore(?StoreSetting $setting = null): self
    {
        $setting ??= StoreSetting::current();

        return new self($setting->pix_discount_percent, $setting->max_installments);
    }

    /** Preço no Pix = preço × (1 − desconto Pix), meio para cima em centavos. */
    public function pixPrice(Money $price): Money
    {
        return $price->percent(100 - $this->pixDiscountPercent);
    }

    /** Valor de cada parcela sem juros. */
    public function installmentAmount(Money $price, ?int $installments = null): Money
    {
        return $price->divideRound($installments ?? $this->maxInstallments);
    }

    /** O preço antigo só vale quando é maior que o preço de venda. */
    public function validOldPrice(Money $price, ?Money $oldPrice): ?Money
    {
        return $oldPrice !== null && $oldPrice->isGreaterThan($price) ? $oldPrice : null;
    }

    /** Percentual de desconto arredondado, ou null quando não há preço antigo válido. */
    public function discountPercent(Money $price, ?Money $oldPrice): ?int
    {
        $old = $this->validOldPrice($price, $oldPrice);

        if ($old === null) {
            return null;
        }

        return intdiv(($old->amount() - $price->amount()) * 200 + $old->amount(), $old->amount() * 2);
    }

    /** Selo do card: "−14%" para desconto, "Novo" para lançamento sem desconto. */
    public function badge(Money $price, ?Money $oldPrice, bool $isNewRelease): ?string
    {
        $percent = $this->discountPercent($price, $oldPrice);

        if ($percent !== null && $percent > 0) {
            return "−{$percent}%";
        }

        return $isNewRelease ? 'Novo' : null;
    }
}
