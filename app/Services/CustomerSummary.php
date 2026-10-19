<?php

namespace App\Services;

use App\Models\Customer;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/** Resumo da ficha da cliente, calculado a partir dos agregados de {@see Customer::scopeWithSummary()}. */
final class CustomerSummary
{
    public function __construct(
        public readonly Money $total,
        public readonly int $purchases,
        public readonly Money $average,
        public readonly Money $openBalance,
        public readonly int $overdueCount,
        public readonly ?CarbonInterface $lastPurchaseAt,
        public readonly ?CarbonInterface $firstPurchaseAt,
    ) {}

    /** @param  Customer  $customer  carregada com o scope withSummary() */
    public static function from(Customer $customer): self
    {
        $purchases = (int) $customer->purchases_count;
        $total = Money::from($customer->total_spent);

        return new self(
            total: $total,
            purchases: $purchases,
            average: $purchases > 0 ? $total->divideRound($purchases) : Money::zero(),
            openBalance: Money::from($customer->open_balance),
            overdueCount: (int) $customer->overdue_count,
            lastPurchaseAt: $customer->last_purchase_at ? Carbon::parse($customer->last_purchase_at) : null,
            firstPurchaseAt: $customer->first_purchase_at ? Carbon::parse($customer->first_purchase_at) : null,
        );
    }

    public static function for(Customer $customer): self
    {
        return self::from(Customer::query()->withSummary()->findOrFail($customer->id));
    }
}
