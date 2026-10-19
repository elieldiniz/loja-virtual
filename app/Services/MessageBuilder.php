<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use App\Support\Money;

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
        $recipient = $answers['recipient'] === 'Para mim' ? 'mim mesma' : $answers['recipient'];

        $lines = ['Oi! Quero ajuda para escolher um presente 🎁', '', 'Para: '.$recipient];

        if (! empty($answers['occasion'])) {
            $lines[] = 'Ocasião: '.$answers['occasion'];
        }

        $lines[] = 'Quanto quero gastar: '.$answers['budget'];

        if (! empty($answers['detail'])) {
            $lines[] = 'Detalhe: '.$answers['detail'];
        }

        return implode("\n", [...$lines, '', 'O que vocês sugerem?']);
    }

    /** Mensagem de convite de ajuda (botão "Falar com a atendente"). */
    public function helpInvite(): string
    {
        return 'Oi! Estou olhando o site e queria uma ajudinha pra escolher uma peça 😊';
    }

    /** Dúvida sobre os cuidados com a peça (guia de cuidados). */
    public function careQuestion(): string
    {
        return 'Oi! Tenho uma dúvida sobre os cuidados com a minha peça:';
    }

    public function receipt(Sale $sale): string
    {
        $sale->loadMissing(['items', 'paymentMethod', 'installments', 'customer']);

        $lines = [
            sprintf(
                'Olá, %s! Segue o resumo da sua compra na Orvalha Semijoias (%s, %s):',
                $this->firstName($sale->customer?->name),
                SaleNumberGenerator::format($sale->number),
                $sale->sold_at->format('d/m/Y'),
            ),
            '',
        ];

        foreach ($sale->items as $item) {
            $lines[] = sprintf('• %d× %s — %s', $item->quantity, $item->product_name, Money::from($item->line_total)->format());
        }

        $discount = Money::from($sale->pix_discount)->add(Money::from($sale->other_discount));

        if (! $discount->isZero()) {
            $lines[] = 'Desconto: − '.$discount->format();
        }

        $lines[] = '';
        $lines[] = '*Total: '.Money::from($sale->total)->format().'*';
        $lines[] = 'Pagamento: '.$this->paymentLabel($sale);

        if ($sale->installments->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'Parcelas:';

            foreach ($sale->installments as $installment) {
                $lines[] = sprintf(
                    '%dª %s — %s%s',
                    $installment->number,
                    $installment->due_date->format('d/m/Y'),
                    Money::from($installment->amount)->format(),
                    $installment->paid_at ? ' (paga)' : '',
                );
            }
        }

        return implode("\n", [...$lines, '', 'Obrigada pela preferência!']);
    }

    /** "Pix", "Crédito 3x", "Crédito à vista", "Crediário 2x". */
    public function paymentLabel(Sale $sale): string
    {
        $name = $sale->paymentMethod->name;

        if ($sale->paymentMethod->slug === 'credito') {
            return $sale->installments_count > 1 ? "{$name} {$sale->installments_count}x" : "{$name} à vista";
        }

        if ($sale->paymentMethod->generates_receivables) {
            return "{$name} {$sale->installments_count}x";
        }

        return $name;
    }

    public function birthday(Customer $customer, int $giftPercent): string
    {
        return sprintf(
            'Oi, %s! Feliz aniversário! 🎉 A Orvalha preparou um presente pra você: %d%% de desconto em qualquer peça até o fim do mês. É só responder esta mensagem 💛',
            $this->firstName($customer->name),
            $giftPercent,
        );
    }

    private function firstName(?string $name): string
    {
        return $name ? explode(' ', trim($name))[0] : 'cliente';
    }
}
