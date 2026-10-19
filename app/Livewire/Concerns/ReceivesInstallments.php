<?php

namespace App\Livewire\Concerns;

use App\Models\Installment;
use App\Services\InstallmentService;
use DomainException;

/** Ação "Receber / Desfazer" reaproveitada na ficha, no detalhe da venda e no Painel. */
trait ReceivesInstallments
{
    public function togglePaid(int $installmentId): void
    {
        try {
            $installment = app(InstallmentService::class)->toggle(Installment::findOrFail($installmentId));
        } catch (DomainException $e) {
            $this->dispatch('toast', message: $e->getMessage(), warn: true);

            return;
        }

        $this->dispatch('toast', message: $installment->paid_at ? 'Parcela recebida' : 'Parcela reaberta');
    }
}
