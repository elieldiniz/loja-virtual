<?php

namespace App\Services\Sales;

use App\Support\Money;
use Carbon\CarbonInterface;

/** Dados de uma venda a registrar (tela Nova venda). */
final class SaleData
{
    /**
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly string $paymentSlug,
        public readonly string $channelSlug,
        public readonly int $installments = 1,
        public readonly ?int $customerId = null,
        public readonly ?CarbonInterface $date = null,
        public readonly ?Money $otherDiscount = null,
        public readonly ?CarbonInterface $firstDueDate = null,
    ) {}
}
