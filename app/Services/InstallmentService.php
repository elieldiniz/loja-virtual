<?php

namespace App\Services;

use App\Models\Installment;
use DomainException;

class InstallmentService
{
    /** Marca a parcela como recebida hoje. */
    public function receive(Installment $installment): Installment
    {
        $installment->loadMissing('sale');

        if ($installment->sale->isCancelled()) {
            throw new DomainException('Parcelas de uma venda cancelada não podem ser recebidas.');
        }

        $installment->update(['paid_at' => today()]);

        return $installment;
    }

    /** Volta a parcela para "em aberto". */
    public function undo(Installment $installment): Installment
    {
        $installment->update(['paid_at' => null]);

        return $installment;
    }

    public function toggle(Installment $installment): Installment
    {
        return $installment->paid_at === null ? $this->receive($installment) : $this->undo($installment);
    }
}
