<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use App\Support\Money;
use Carbon\CarbonInterface;

class MessageBuilder
{
    /**
     * Mensagem da sacola enviada ao WhatsApp da loja.
     *
     * @param  list<array{name: string, material: string, quantity: int, unit_price: Money}>  $items
     */
    public function bag(array $items, Money $total, Money $pixTotal): string
    {
        $lines = array_map(
            fn (array $item) => sprintf(
                '• %dx %s (%s) — %s',
                $item['quantity'],
                $item['name'],
                $item['material'],
                $item['unit_price']->times($item['quantity'])->format(),
            ),
            $items,
        );

        return implode("\n", [
            'Oi! Separei essas peças no site da Orvalha:',
            '',
            ...$lines,
            '',
            sprintf('Total: %s (ou %s no Pix)', $total->format(), $pixTotal->format()),
            '',
            'Pode confirmar se tem tudo disponível?',
        ]);
    }

    public function productQuestion(string $productName): string
    {
        return "Oi! Tenho uma dúvida sobre a peça {$productName}. Pode me ajudar?";
    }

    /** @param  array{recipient: string, occasion?: ?string, budget: string, detail?: ?string}  $answers */
    public function gift(array $answers): string
    {
        $lines = [
            'Oi! Preciso de ajuda para escolher um presente.',
            '',
            'É para: '.$answers['recipient'],
        ];

        if (! empty($answers['occasion'])) {
            $lines[] = 'Ocasião: '.$answers['occasion'];
        }

        $lines[] = 'Quero gastar: '.$answers['budget'];

        if (! empty($answers['detail'])) {
            $lines[] = 'Detalhe: '.$answers['detail'];
        }

        return implode("\n", $lines);
    }

    public function receipt(Sale $sale): string
    {
        $sale->loadMissing(['items', 'paymentMethod', 'installments', 'customer']);

        $lines = [
            sprintf('Oi, %s! Segue o recibo da sua compra na Orvalha:', $this->firstName($sale->customer?->name)),
            '',
            sprintf('Venda %s — %s', SaleNumberGenerator::format($sale->number), $sale->sold_at->format('d/m/Y')),
            '',
        ];

        foreach ($sale->items as $item) {
            $lines[] = sprintf('• %dx %s — %s', $item->quantity, $item->product_name, Money::from($item->line_total)->format());
        }

        $discount = Money::from($sale->pix_discount)->add(Money::from($sale->other_discount));
        $lines[] = '';

        if (! $discount->isZero()) {
            $lines[] = 'Desconto: '.$discount->format();
        }

        $lines[] = 'Total: '.Money::from($sale->total)->format();
        $lines[] = 'Pagamento: '.$sale->paymentMethod->name.($sale->installments_count > 1 ? " em {$sale->installments_count}x" : '');

        if ($sale->installments->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'Parcelas:';

            foreach ($sale->installments as $installment) {
                $lines[] = sprintf(
                    '%d/%d — %s — %s',
                    $installment->number,
                    $sale->installments->count(),
                    $installment->due_date->format('d/m/Y'),
                    Money::from($installment->amount)->format(),
                );
            }
        }

        $lines[] = '';
        $lines[] = 'Obrigada pela preferência!';

        return implode("\n", $lines);
    }

    public function birthday(Customer $customer, int $giftPercent, CarbonInterface $validUntil): string
    {
        return sprintf(
            'Parabéns, %s! A Orvalha te deseja um dia lindo. De presente, você ganha %d%% de desconto em qualquer peça até %s.',
            $this->firstName($customer->name),
            $giftPercent,
            $validUntil->format('d/m'),
        );
    }

    private function firstName(?string $name): string
    {
        return $name ? explode(' ', trim($name))[0] : 'cliente';
    }
}
