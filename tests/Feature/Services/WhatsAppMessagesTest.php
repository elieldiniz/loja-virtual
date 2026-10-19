<?php

use App\Models\Customer;
use App\Models\Installment;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreSetting;
use App\Services\MessageBuilder;
use App\Services\WhatsAppLink;
use App\Support\Money;
use Illuminate\Support\Carbon;

it('builds an encoded wa.me link with the message', function () {
    $url = WhatsAppLink::make('(69) 99999-0000', "Olá, tudo bem?\nQuero 2 peças • Ouro");

    expect($url)->toStartWith('https://wa.me/5569999990000?text=');

    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    expect($query['text'])->toBe("Olá, tudo bem?\nQuero 2 peças • Ouro");
});

it('uses the store whatsapp and fails without one', function () {
    StoreSetting::current()->update(['whatsapp' => '69 99999-0000']);

    expect(WhatsAppLink::toStore('Oi'))->toContain('https://wa.me/5569999990000');

    StoreSetting::current()->update(['whatsapp' => null]);

    expect(fn () => WhatsAppLink::toStore('Oi'))->toThrow(RuntimeException::class);
});

it('builds the bag message exactly like the spec example', function () {
    $message = (new MessageBuilder)->bag([
        ['name' => 'Colar Ponto de Luz', 'material' => 'Ouro 18k', 'quantity' => 1, 'unit_price' => Money::from('99.90')],
        ['name' => 'Argola Lisa Média', 'material' => 'Ouro 18k', 'quantity' => 2, 'unit_price' => Money::from('89.90')],
    ], Money::from('279.70'), Money::from('265.72'));

    expect($message)->toBe(<<<'TXT'
Oi! Separei essas peças no site da Orvalha:

• 1x Colar Ponto de Luz (Ouro 18k) — R$ 99,90
• 2x Argola Lisa Média (Ouro 18k) — R$ 179,80

Total: R$ 279,70 (ou R$ 265,72 no Pix)

Pode confirmar se tem tudo disponível?
TXT);
});

it('builds the gift message and omits empty optional answers', function () {
    $full = (new MessageBuilder)->gift(['recipient' => 'Mãe', 'occasion' => 'Aniversário', 'budget' => 'R$ 100 a R$ 200', 'detail' => 'Gosta de dourado']);
    $short = (new MessageBuilder)->gift(['recipient' => 'Para mim', 'occasion' => null, 'budget' => 'até R$ 100', 'detail' => '']);

    expect($full)->toContain('É para: Mãe', 'Ocasião: Aniversário', 'Quero gastar: R$ 100 a R$ 200', 'Detalhe: Gosta de dourado')
        ->and($short)->toContain('É para: Para mim', 'Quero gastar: até R$ 100')
        ->and($short)->not->toContain('Ocasião')->not->toContain('Detalhe');
});

it('builds the product question message', function () {
    expect((new MessageBuilder)->productQuestion('Anel Solitário'))->toContain('Anel Solitário');
});

it('builds a receipt with items, discount, payment and installments', function () {
    $crediario = PaymentMethod::factory()->create(['name' => 'Crediário', 'slug' => 'crediario', 'allows_installments' => true]);
    $customer = Customer::factory()->create(['name' => 'Mariana Souza']);
    $sale = Sale::factory()->create([
        'number' => 616, 'customer_id' => $customer->id, 'payment_method_id' => $crediario->id,
        'installments_count' => 2, 'subtotal' => 100, 'other_discount' => 10, 'total' => 90, 'sold_at' => Carbon::parse('2026-10-19 10:00'),
    ]);
    SaleItem::factory()->create(['sale_id' => $sale->id, 'product_name' => 'Colar', 'unit_price' => 100, 'quantity' => 1, 'line_total' => 100]);
    Installment::factory()->create(['sale_id' => $sale->id, 'number' => 1, 'amount' => 45, 'due_date' => '2026-11-19']);
    Installment::factory()->create(['sale_id' => $sale->id, 'number' => 2, 'amount' => 45, 'due_date' => '2026-12-19']);

    $message = (new MessageBuilder)->receipt($sale->fresh());

    expect($message)->toContain('Oi, Mariana!', 'Venda #0616 — 19/10/2026', '• 1x Colar — R$ 100,00', 'Desconto: R$ 10,00', 'Total: R$ 90,00', 'Pagamento: Crediário em 2x', '1/2 — 19/11/2026 — R$ 45,00', '2/2 — 19/12/2026 — R$ 45,00');
});

it('omits installments and discount in a cash receipt', function () {
    $sale = Sale::factory()->create(['number' => 7]);
    SaleItem::factory()->create(['sale_id' => $sale->id]);

    $message = (new MessageBuilder)->receipt($sale->fresh());

    expect($message)->not->toContain('Parcelas')->not->toContain('Desconto');
});

it('builds the birthday message with the configured gift percent', function () {
    $customer = Customer::factory()->make(['name' => 'Ana Lima']);

    $message = (new MessageBuilder)->birthday($customer, 10, Carbon::parse('2026-10-31'));

    expect($message)->toContain('Parabéns, Ana!', '10% de desconto', 'até 31/10');
});
