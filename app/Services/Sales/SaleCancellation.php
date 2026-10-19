<?php

namespace App\Services\Sales;

use App\Models\Sale;
use App\Support\Money;
use DomainException;
use Illuminate\Validation\ValidationException;

class SaleCancellation
{
    /** Valor do crediário já recebido (a loja combina a devolução). */
    public function receivedAmount(Sale $sale): Money
    {
        return Money::from($sale->installments()->whereNotNull('paid_at')->sum('amount'));
    }

    /**
     * Cancela a venda com um motivo. Devolve o valor já recebido no crediário, para o aviso.
     *
     * @throws ValidationException
     */
    public function cancel(Sale $sale, string $reason): Money
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Informe o motivo do cancelamento.']);
        }

        if ($sale->isCancelled()) {
            throw new DomainException('Esta venda já está cancelada.');
        }

        $sale->update(['cancelled_at' => now(), 'cancel_reason' => $reason]);

        return $this->receivedAmount($sale);
    }

    public function reactivate(Sale $sale): void
    {
        if (! $sale->isCancelled()) {
            throw new DomainException('Esta venda não está cancelada.');
        }

        $sale->update(['cancelled_at' => null, 'cancel_reason' => null]);
    }
}
