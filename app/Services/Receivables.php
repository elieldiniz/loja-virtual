<?php

namespace App\Services;

use App\Models\Installment;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Crediário a receber: parcelas em aberto de vendas não canceladas (independe de período). */
class Receivables
{
    /** @return Builder<Installment> */
    public function openQuery(): Builder
    {
        return Installment::query()
            ->whereNull('installments.paid_at')
            ->whereHas('sale', fn ($q) => $q->whereNull('cancelled_at'));
    }

    public function openTotal(): Money
    {
        return Money::from($this->openQuery()->sum('amount'));
    }

    public function openCount(): int
    {
        return $this->openQuery()->count();
    }

    public function overdueTotal(): Money
    {
        return Money::from($this->openQuery()->whereDate('due_date', '<', today()->toDateString())->sum('amount'));
    }

    public function overdueCount(): int
    {
        return $this->openQuery()->whereDate('due_date', '<', today()->toDateString())->count();
    }

    /** @return Collection<int, Installment> */
    public function next(int $limit = 5): Collection
    {
        return $this->openQuery()
            ->with(['sale.customer'])
            ->orderBy('due_date')->orderBy('id')
            ->limit($limit)->get();
    }
}
