<?php

use App\Models\Category;
use App\Models\Material;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use Database\Seeders\LookupSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(LookupSeeder::class);
    StoreSetting::current()->update(['whatsapp' => '69999990000', 'pix_discount_percent' => 5, 'max_installments' => 3]);
    $category = Category::factory()->create(['name' => 'Colares']);
    $material = Material::where('slug', 'ouro-18k')->first();
    $this->colar = Product::factory()->active()->withPhotos()->create(['category_id' => $category->id, 'material_id' => $material->id, 'name' => 'Colar Ponto de Luz', 'price' => 99.90]);
    $this->argola = Product::factory()->active()->withPhotos()->create(['category_id' => $category->id, 'material_id' => $material->id, 'name' => 'Argola Lisa Média', 'price' => 89.90]);
});

it('computes bag totals from the server prices', function () {
    $response = $this->getJson(route('storefront.bag', ['i' => "{$this->colar->id}:1,{$this->argola->id}:2"]))->assertOk();

    expect($response->json('subtotal'))->toBe('R$ 279,70')
        ->and($response->json('pix_total'))->toBe('R$ 265,72')
        ->and($response->json('count'))->toBe(3)
        ->and($response->json('installments'))->toBe(3)
        ->and($response->json('installment_amount'))->toBe('R$ 93,23')
        ->and($response->json('lines.1.total'))->toBe('R$ 179,80');
});

it('builds the WhatsApp message exactly like the spec', function () {
    $url = $this->getJson(route('storefront.bag', ['i' => "{$this->colar->id}:1,{$this->argola->id}:2"]))->json('whatsapp_url');

    parse_str(parse_url($url, PHP_URL_QUERY), $query);

    expect($url)->toStartWith('https://wa.me/5569999990000?text=')
        ->and($query['text'])->toBe("Oi! Separei essas peças no site da Orvalha:\n\n• 1x Colar Ponto de Luz (Ouro 18k) — R$ 99,90\n• 2x Argola Lisa Média (Ouro 18k) — R$ 179,80\n\nTotal: R$ 279,70 (ou R$ 265,72 no Pix)\n\nPode confirmar se tem tudo disponível?");
});

it('drops hidden, missing and zero quantity pieces and reports the removed ids', function () {
    $this->argola->update(['is_active' => false]);

    $response = $this->getJson(route('storefront.bag', ['i' => "{$this->colar->id}:1,{$this->argola->id}:1,99999:2,{$this->colar->id}x:1"]))->assertOk();

    expect($response->json('lines'))->toHaveCount(1)
        ->and($response->json('removed'))->toEqualCanonicalizing([$this->argola->id, 99999])
        ->and($response->json('subtotal'))->toBe('R$ 99,90');

    $zero = $this->getJson(route('storefront.bag', ['i' => "{$this->colar->id}:0"]))->json();
    expect($zero['lines'])->toBe([])->and($zero['whatsapp_url'])->toBeNull();
});

it('reflects a price change in the totals', function () {
    $this->colar->update(['price' => 120]);

    expect($this->getJson(route('storefront.bag', ['i' => "{$this->colar->id}:2"]))->json('subtotal'))->toBe('R$ 240,00');
});

it('does not create a sale or any record when summarizing the bag', function () {
    $this->getJson(route('storefront.bag', ['i' => "{$this->colar->id}:1"]))->assertOk();

    expect(Sale::count())->toBe(0);
});

it('returns a safe link when the store has no whatsapp', function () {
    StoreSetting::current()->update(['whatsapp' => null]);

    expect($this->getJson(route('storefront.bag', ['i' => "{$this->colar->id}:1"]))->json('whatsapp_url'))->toBe('#');
});

it('returns the product page data only for visible products', function () {
    $this->getJson(route('storefront.product', $this->colar->id))->assertOk()
        ->assertJsonPath('name', 'Colar Ponto de Luz')->assertJsonPath('material', 'Ouro 18k')->assertJsonPath('price', 'R$ 99,90')
        ->assertJsonPath('pix_price', 'R$ 94,91')->assertJsonCount(1, 'photos');

    $this->colar->update(['is_active' => false]);
    $this->getJson(route('storefront.product', $this->colar->id))->assertNotFound();
});

it('builds the "tirar dúvida" link with the product name', function () {
    $url = $this->getJson(route('storefront.product', $this->colar->id))->json('question_url');
    parse_str(parse_url($url, PHP_URL_QUERY), $query);

    expect($query['text'])->toContain('Colar Ponto de Luz');
});

it('redirects the gift form to the store whatsapp with the answers', function () {
    $response = $this->get(route('storefront.gift', ['para' => 'Mãe', 'ocasiao' => 'Aniversário', 'valor' => 'R$ 100 a R$ 200', 'detalhe' => 'Gosta de prata']));

    $response->assertRedirectContains('https://wa.me/5569999990000?text=');
    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
    expect($query['text'])->toContain('Para: Mãe', 'Ocasião: Aniversário', 'Quanto quero gastar: R$ 100 a R$ 200', 'Detalhe: Gosta de prata');
});

it('rejects gift answers outside the allowed options', function () {
    $this->get(route('storefront.gift', ['para' => 'Vizinha', 'valor' => 'R$ 100 a R$ 200']))->assertSessionHasErrors('para');
    $this->get(route('storefront.gift', ['para' => 'Mãe', 'valor' => 'de graça']))->assertSessionHasErrors('valor');
});
