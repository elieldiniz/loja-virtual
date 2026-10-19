@props(['installment', 'showClient' => false])
@php
    use App\Enums\InstallmentStatus;
    use App\Support\Money;

    $i = $installment;
    $status = $i->status();
    $days = $i->daysUntilDue();
    $sale = $i->sale;
@endphp
<div class="parcel {{ $status === InstallmentStatus::Paid ? 'paid' : '' }}">
    <span class="pdue">{{ $i->due_date->format('d/m') }}
        @if ($status === InstallmentStatus::Paid)
            <small>Paga em {{ $i->paid_at->format('d/m') }}</small>
        @elseif ($status === InstallmentStatus::Overdue)
            <small class="late"><x-icon name="alert" />Vencida</small>
        @elseif ($status === InstallmentStatus::DueSoon)
            <small>{{ $days === 0 ? 'Vence hoje' : 'Vence em '.$days.' '.($days === 1 ? 'dia' : 'dias') }}</small>
        @else
            <small>Em aberto</small>
        @endif
    </span>
    <span class="pwho">
        @if ($showClient)<b>{{ $sale->customer?->name ?? '—' }}</b>@endif
        <small>Parcela {{ $i->number }} de {{ $sale->installments_count }} · venda de {{ $sale->sold_at->format('d/m') }}</small>
    </span>
    <span class="pval">{{ Money::from($i->amount)->format() }}
        <button class="link-btn" type="button" wire:click="togglePaid({{ $i->id }})" wire:loading.attr="disabled">{{ $status === InstallmentStatus::Paid ? 'Desfazer' : 'Receber' }}</button>
    </span>
</div>
