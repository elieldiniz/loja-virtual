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
Oi! Separei essas peças no site da STyli Prates:

• 1x Colar Ponto de Luz (Ouro 18k) — R$ 99,90
• 2x Argola Lisa Média (Ouro 18k) — R$ 179,80

Total: R$ 279,70 (ou R$ 265,72 no Pix)

Pode confirmar se tem tudo disponível?
TXT);
});

it('builds the gift message and omits empty optional answers', function () {
    $full = (new MessageBuilder)->gift(['recipient' => 'Mãe', 'occasion' => 'Aniversário', 'budget' => 'R$ 100 a R$ 200', 'detail' => 'Gosta de dourado']);
    $short = (new MessageBuilder)->gift(['recipient' => 'Para mim', 'occasion' => null, 'budget' => 'Até R$ 100', 'detail' => '']);

    expect($full)->toContain('Para: Mãe', 'Ocasião: Aniversário', 'Quanto quero gastar: R$ 100 a R$ 200', 'Detalhe: Gosta de dourado', 'O que vocês sugerem?')
        ->and($short)->toContain('Para: mim mesma', 'Quanto quero gastar: Até R$ 100')
        ->and($short)->not->toContain('Ocasião')->not->toContain('Detalhe');
});

it('builds the product question message', function () {
    expect((new MessageBuilder)->productQuestion('Anel Solitário'))->toContain('Anel Solitário');
});

it('builds a receipt with items, discount, payment and installments', function () {
    $crediario = PaymentMethod::factory()->create(['name' => 'Crediário', 'slug' => 'crediario', 'allows_installments' => true, 'generates_receivables' => true]);
    $customer = Customer::factory()->create(['name' => 'Mariana Souza']);
    $sale = Sale::factory()->create([
        'number' => 616, 'customer_id' => $customer->id, 'payment_method_id' => $crediario->id,
        'installments_count' => 2, 'subtotal' => 100, 'other_discount' => 10, 'total' => 90, 'sold_at' => Carbon::parse('2026-10-19 10:00'),
    ]);
    SaleItem::factory()->create(['sale_id' => $sale->id, 'product_name' => 'Colar', 'unit_price' => 100, 'quantity' => 1, 'line_total' => 100]);
    Installment::factory()->create(['sale_id' => $sale->id, 'number' => 1, 'amount' => 45, 'due_date' => '2026-11-19', 'paid_at' => '2026-11-18']);
    Installment::factory()->create(['sale_id' => $sale->id, 'number' => 2, 'amount' => 45, 'due_date' => '2026-12-19']);

    $message = (new MessageBuilder)->receipt($sale->fresh());

    expect($message)->toBe(<<<'TXT'
Olá, Mariana! Segue o resumo da sua compra na STyli Prates (#0616, 19/10/2026):

• 1× Colar — R$ 100,00
Desconto: − R$ 10,00

*Total: R$ 90,00*
Pagamento: Crediário 2x

Parcelas:
1ª 19/11/2026 — R$ 45,00 (paga)
2ª 19/12/2026 — R$ 45,00

Obrigada pela preferência!
TXT);
});

it('omits installments and discount in a cash receipt', function () {
    $sale = Sale::factory()->create(['number' => 7]);
    SaleItem::factory()->create(['sale_id' => $sale->id]);

    $message = (new MessageBuilder)->receipt($sale->fresh());

    expect($message)->not->toContain('Parcelas')->not->toContain('Desconto');
});

it('labels the payment like the mockup', function () {
    $credito = PaymentMethod::factory()->create(['name' => 'Crédito', 'slug' => 'credito']);
    $pix = PaymentMethod::factory()->create(['name' => 'Pix', 'slug' => 'pix']);
    $builder = new MessageBuilder;

    expect($builder->paymentLabel(Sale::factory()->create(['payment_method_id' => $credito->id, 'installments_count' => 3])->load('paymentMethod')))->toBe('Crédito 3x')
        ->and($builder->paymentLabel(Sale::factory()->create(['payment_method_id' => $credito->id, 'installments_count' => 1])->load('paymentMethod')))->toBe('Crédito à vista')
        ->and($builder->paymentLabel(Sale::factory()->create(['payment_method_id' => $pix->id])->load('paymentMethod')))->toBe('Pix');
});

it('builds the birthday message with the configured gift percent', function () {
    $customer = Customer::factory()->make(['name' => 'Ana Lima']);

    $message = (new MessageBuilder)->birthday($customer, 10);

    expect($message)->toContain('Oi, Ana!', 'Feliz aniversário', '10% de desconto', 'até o fim do mês');
});
