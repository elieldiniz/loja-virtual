<?php

namespace App\Livewire\Admin\Sales;

use App\Livewire\Concerns\ReceivesInstallments;
use App\Models\Sale;
use App\Services\MessageBuilder;
use App\Services\Sales\SaleCancellation;
use App\Services\WhatsAppLink;
use App\Support\Money;
use DomainException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Show extends Component
{
    use ReceivesInstallments;

    public int $saleId;

    public bool $confirmingCancel = false;

    public string $reason = '';

    public function mount(Sale $sale): void
    {
        $this->saleId = $sale->id;

        if (session()->has('status')) {
            $this->dispatch('toast', message: session('status'));
        }
    }

    public function askCancel(): void
    {
        $this->reason = '';
        $this->resetErrorBag();
        $this->confirmingCancel = true;
    }

    public function closeCancel(): void
    {
        $this->confirmingCancel = false;
    }

    public function cancel(SaleCancellation $cancellation): void
    {
        try {
            $received = $cancellation->cancel(Sale::findOrFail($this->saleId), $this->reason);
        } catch (DomainException $e) {
            $this->dispatch('toast', message: $e->getMessage(), warn: true);

            return;
        }

        $this->confirmingCancel = false;

        $message = 'Venda cancelada';
        if (! $received->isZero()) {
            $message .= ': já foram recebidos '.$received->format().' do crediário, combine a devolução';
        }

        $this->dispatch('toast', message: $message, warn: ! $received->isZero());
    }

    public function reactivate(SaleCancellation $cancellation): void
    {
        try {
            $cancellation->reactivate(Sale::findOrFail($this->saleId));
        } catch (DomainException $e) {
            $this->dispatch('toast', message: $e->getMessage(), warn: true);

            return;
        }

        $this->dispatch('toast', message: 'Venda reativada');
    }

    public function render(SaleCancellation $cancellation, MessageBuilder $messages)
    {
        $sale = Sale::query()->with(['items.product.media', 'installments.sale', 'paymentMethod', 'channel', 'customer'])->findOrFail($this->saleId);

        $receiptUrl = null;
        if (! $sale->isCancelled() && $sale->customer?->whatsapp) {
            $receiptUrl = WhatsAppLink::make($sale->customer->whatsapp, $messages->receipt($sale));
        }

        return view('livewire.admin.sales.show', [
            'sale' => $sale,
            'receiptUrl' => $receiptUrl,
            'received' => $cancellation->receivedAmount($sale),
            'paymentLabel' => $messages->paymentLabel($sale),
            'pieces' => $sale->items->sum('quantity'),
            'total' => Money::from($sale->total),
        ])->layoutData(['current' => 'sales', 'title' => 'Venda']);
    }
}
