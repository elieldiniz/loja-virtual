<?php

namespace App\Services\Sales;

use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleChannel;
use App\Models\StoreSetting;
use App\Services\InstallmentSplitter;
use App\Services\SaleCalculator;
use App\Services\SaleNumberGenerator;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleRegistrar
{
    public function __construct(
        private readonly SaleCalculator $calculator,
        private readonly InstallmentSplitter $splitter,
        private readonly SaleNumberGenerator $numbers,
    ) {}

    /**
     * @throws ValidationException
     */
    public function register(SaleData $data): Sale
    {
        $method = PaymentMethod::query()->where('slug', $data->paymentSlug)->where('is_active', true)->first()
            ?? $this->fail('payment', 'Escolha a forma de pagamento.');
        $channel = SaleChannel::query()->where('slug', $data->channelSlug)->where('is_active', true)->first()
            ?? $this->fail('channel', 'Escolha onde a venda foi fechada.');

        $lines = $this->resolveItems($data);
        $this->guardInstallments($method, $data->installments);

        if ($method->generates_receivables && $data->customerId === null) {
            $this->fail('customer', 'No crediário a cliente é obrigatória: escolha ou cadastre uma.');
        }

        if ($data->customerId !== null && ! Customer::whereKey($data->customerId)->exists()) {
            $this->fail('customer', 'Cliente não encontrada.');
        }

        $soldAt = $this->soldAt($data);
        $subtotal = array_reduce($lines, fn (Money $carry, array $l) => $carry->add($l['line_total']), Money::zero());
        $totals = $this->calculator->totals($subtotal, $method, $data->otherDiscount, StoreSetting::current()->pix_discount_percent);

        $firstDue = $method->generates_receivables
            ? ($data->firstDueDate ?? $soldAt->copy()->addMonthNoOverflow())
            : null;

        return DB::transaction(function () use ($data, $method, $channel, $lines, $soldAt, $totals, $firstDue) {
            $sale = Sale::create([
                'number' => $this->numbers->next(),
                'customer_id' => $data->customerId,
                'payment_method_id' => $method->id,
                'sale_channel_id' => $channel->id,
                'installments_count' => $data->installments,
                'first_due_date' => $firstDue?->toDateString(),
                'subtotal' => $totals['subtotal']->toDecimal(),
                'pix_discount' => $totals['pix_discount']->toDecimal(),
                'other_discount' => $totals['other_discount']->toDecimal(),
                'total' => $totals['total']->toDecimal(),
                'sold_at' => $soldAt,
            ]);

            foreach ($lines as $line) {
                $sale->items()->create([
                    'product_id' => $line['product']->id,
                    'category_id' => $line['product']->category_id,
                    'product_name' => $line['product']->name,
                    'category_name' => $line['product']->category->name,
                    'material_name' => $line['product']->material->name,
                    'unit_price' => $line['unit_price']->toDecimal(),
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total']->toDecimal(),
                ]);
            }

            if ($method->generates_receivables) {
                foreach ($this->splitter->split($totals['total'], $data->installments, $firstDue) as $part) {
                    $sale->installments()->create([
                        'number' => $part['number'],
                        'amount' => $part['amount']->toDecimal(),
                        'due_date' => $part['due_date']->toDateString(),
                    ]);
                }
            }

            return $sale->load(['items', 'installments', 'paymentMethod', 'channel', 'customer']);
        });
    }

    /** @return list<array{product: Product, quantity: int, unit_price: Money, line_total: Money}> */
    private function resolveItems(SaleData $data): array
    {
        $merged = [];

        foreach ($data->items as $item) {
            $merged[$item['product_id']] = ($merged[$item['product_id']] ?? 0) + (int) $item['quantity'];
        }

        if ($merged === [] || min($merged) < 1) {
            $this->fail('items', 'Adicione pelo menos uma peça à venda.');
        }

        $products = Product::query()->with(['category', 'material'])->whereIn('id', array_keys($merged))->get()->keyBy('id');

        $lines = [];
        foreach ($merged as $productId => $quantity) {
            $product = $products->get($productId) ?? $this->fail('items', 'Uma das peças não existe mais.');
            $unit = Money::from($product->price);
            $lines[] = ['product' => $product, 'quantity' => $quantity, 'unit_price' => $unit, 'line_total' => $unit->times($quantity)];
        }

        return $lines;
    }

    private function guardInstallments(PaymentMethod $method, int $installments): void
    {
        $max = $method->allows_installments ? $method->max_installments : 1;

        if ($installments < 1 || $installments > $max) {
            $this->fail('installments', $max === 1
                ? "{$method->name} só aceita pagamento à vista."
                : "{$method->name} aceita de 1 a {$max} parcelas.");
        }
    }

    private function soldAt(SaleData $data): CarbonInterface
    {
        $date = $data->date?->copy() ?? today();

        if ($date->copy()->startOfDay()->gt(today())) {
            $this->fail('date', 'A data da venda não pode ser no futuro.');
        }

        return now()->setDate($date->year, $date->month, $date->day);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
