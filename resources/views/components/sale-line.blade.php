@props(['sale', 'showCustomer' => true])
@php
    use App\Services\MessageBuilder;
    use App\Services\SaleNumberGenerator;
    use App\Support\Format;
    use App\Support\Money;

    $count = $sale->items->sum('quantity');
    $names = $sale->items->map(fn ($item) => ($item->quantity > 1 ? $item->quantity.'× ' : '').$item->product_name)->implode(' + ');
@endphp
<a class="sale" href="{{ route('admin.sales.show', $sale) }}" wire:key="sale-line-{{ $sale->id }}">
    <span class="when">{{ Format::whenLabel($sale->sold_at) }}</span>
    <span class="its">
        @if ($showCustomer && $sale->customer)<b class="who">{{ $sale->customer->name }}</b>@endif
        {{ $names }}
        <small>{{ SaleNumberGenerator::format($sale->number) }} · {{ Format::pieces($count) }} · {{ app(MessageBuilder::class)->paymentLabel($sale) }} · {{ $sale->channel->name }}</small>
    </span>
    <span class="tot">{{ Money::from($sale->total)->format() }}</span>
    <x-icon name="right" />
</a>
