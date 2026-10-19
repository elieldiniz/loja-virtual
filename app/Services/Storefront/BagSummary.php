<?php

namespace App\Services\Storefront;

use App\Models\Product;
use App\Services\MessageBuilder;
use App\Services\PricingCalculator;
use App\Support\Money;

/**
 * Totais da sacola. A sacola vive só no navegador (ids e quantidades); preços, peças ativas
 * e a mensagem do WhatsApp são sempre calculados aqui, com os dados atuais do banco.
 */
class BagSummary
{
    public function __construct(
        private readonly StorefrontCatalog $catalog,
        private readonly MessageBuilder $messages,
        private readonly StorefrontLinks $links,
    ) {}

    /**
     * @param  array<int|string, int|string>  $quantities  id da peça => quantidade
     * @return array<string, mixed>
     */
    public function summarize(array $quantities): array
    {
        $wanted = [];
        foreach ($quantities as $id => $quantity) {
            $quantity = (int) $quantity;
            if ((int) $id > 0 && $quantity > 0) {
                $wanted[(int) $id] = min($quantity, 99);
            }
        }

        $products = $this->catalog->byIds(array_keys($wanted));
        $pricing = PricingCalculator::forStore();

        $lines = [];
        $messageItems = [];
        $subtotal = Money::zero();

        foreach ($wanted as $id => $quantity) {
            /** @var Product|null $product */
            $product = $products->firstWhere('id', $id);

            if ($product === null) {
                continue;
            }

            $unit = Money::from($product->price);
            $total = $unit->times($quantity);
            $subtotal = $subtotal->add($total);

            $lines[] = [
                'id' => $product->id,
                'name' => $product->name,
                'material' => $product->material->name,
                'quantity' => $quantity,
                'unit_price' => $unit->format(),
                'total' => $total->format(),
                'thumb' => $product->cover()?->getUrl('thumb'),
            ];
            $messageItems[] = ['name' => $product->name, 'material' => $product->material->name, 'quantity' => $quantity, 'unit_price' => $unit];
        }

        $pix = $pricing->pixPrice($subtotal);

        return [
            'lines' => $lines,
            'removed' => array_values(array_diff(array_keys($wanted), array_column($lines, 'id'))),
            'count' => array_sum(array_column($lines, 'quantity')),
            'subtotal' => $subtotal->format(),
            'pix_total' => $pix->format(),
            'pix_percent' => $pricing->pixDiscountPercent,
            'installments' => $pricing->maxInstallments,
            'installment_amount' => $pricing->installmentAmount($subtotal)->format(),
            'whatsapp_url' => $lines === [] ? null : $this->links->whatsapp($this->messages->bag($messageItems, $subtotal, $pix)),
        ];
    }
}
