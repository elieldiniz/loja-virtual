<?php

use App\Livewire\Storefront\Home;
use App\Models\Category;
use App\Models\ChatShortcut;
use App\Models\Material;
use App\Models\Product;
use App\Models\PromoPhrase;
use App\Models\StoreSetting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(DatabaseSeeder::class);
    $this->brincos = Category::factory()->create(['name' => 'Brincos', 'position' => 1]);
    $this->colares = Category::factory()->create(['name' => 'Colares', 'position' => 2]);
    $this->gold = Material::where('slug', 'ouro-18k')->first();
    $this->silver = Material::where('slug', 'prata-925')->first();
});

function shopProduct(array $attrs = []): Product
{
    return Product::factory()->active()->withPhotos()->create($attrs + ['category_id' => test()->brincos->id, 'material_id' => test()->gold->id]);
}

it('renders the home page for visitors', function () {
    $this->get('/')->assertOk()->assertSee('STyli Prates')->assertSee('Peças para')->assertSee('Como funciona comprar');
});

it('shows best sellers by default with price, installments and pix and without the material on the card', function () {
    shopProduct(['name' => 'Argola Lisa', 'price' => 99.90, 'is_best_seller' => true]);
    shopProduct(['name' => 'Peça fora dos mais vendidos']);

    Livewire::test(Home::class)
        ->assertSee('Mais vendidos')->assertSee('Argola Lisa')->assertSee('R$ 99,90')->assertSee('3x de R$ 33,30')->assertSee('R$ 94,91 no Pix')
        ->assertDontSee('Peça fora dos mais vendidos');
});

it('never shows hidden or photoless products', function () {
    Product::factory()->active()->create(['name' => 'Sem foto', 'is_best_seller' => true, 'category_id' => $this->brincos->id, 'material_id' => $this->gold->id]);
    Product::factory()->withPhotos()->create(['name' => 'Oculta', 'is_best_seller' => true, 'category_id' => $this->brincos->id, 'material_id' => $this->gold->id]);

    Livewire::test(Home::class)->assertDontSee('Sem foto')->assertDontSee('Oculta');
});

it('lists categories in the panel order in the menu', function () {
    Livewire::test(Home::class)->assertSeeInOrder(['Todas', 'Brincos', 'Colares', 'Promoções']);
});

it('filters by category and promotions', function () {
    shopProduct(['name' => 'Brinco A']);
    shopProduct(['name' => 'Colar B', 'category_id' => $this->colares->id, 'material_id' => $this->silver->id, 'price' => 100, 'old_price' => 140]);

    Livewire::test(Home::class)
        ->call('selectCategory', (string) $this->colares->id)->assertSee('Colar B')->assertDontSee('Brinco A')->assertSee('Categoria')
        ->call('selectCategory', 'sale')->assertSee('Promoções')->assertSee('Colar B')->assertDontSee('Brinco A')
        ->call('clearFilters')->assertSee('Mais vendidos');
});

it('searches while typing by name and category ignoring accents', function () {
    shopProduct(['name' => 'Coração Vazado']);
    shopProduct(['name' => 'Pingente Estrela', 'category_id' => $this->colares->id]);

    Livewire::test(Home::class)
        ->set('q', 'coracao')->assertSee('Busca: "coracao"')->assertSee('Coração Vazado')->assertDontSee('Pingente Estrela')
        ->set('q', 'colares')->assertSee('Pingente Estrela')->assertDontSee('Coração Vazado')
        ->set('q', 'zzz')->assertSee('Nenhuma peça encontrada')->assertSee('Falar com a gente');
});

it('shows the invitation to talk to the store when the search has no results', function () {
    StoreSetting::current()->update(['whatsapp' => '69999990000']);

    Livewire::test(Home::class)->set('q', 'inexistente')->assertSee('https://wa.me/5569999990000', false);
});

it('shows favorites hydrated from the device ignoring hidden products', function () {
    $a = shopProduct(['name' => 'Favorita Visível']);
    $hidden = Product::factory()->create(['name' => 'Favorita Oculta', 'category_id' => $this->brincos->id, 'material_id' => $this->gold->id]);

    Livewire::test(Home::class)
        ->call('showFavorites', [$a->id, $hidden->id, 99999, 'abc'])
        ->assertSee('Seus favoritos')->assertSee('Favorita Visível')->assertDontSee('Favorita Oculta');
});

it('limits new releases to 4 in the releases section', function () {
    foreach (range(1, 6) as $i) {
        shopProduct(['name' => "Lançamento {$i}", 'is_new_release' => true]);
    }

    $component = Livewire::test(Home::class)->assertSee('Lançamentos');
    $html = $component->html();

    expect(substr_count($html, 'class="tag new"'))->toBe(4);
});

it('renders the promo phrases from the panel and hides the bar without phrases', function () {
    Livewire::test(Home::class)->assertSee('Atendimento de verdade pelo WhatsApp')->assertSee('5% de desconto no Pix');

    PromoPhrase::query()->delete();
    Livewire::test(Home::class)->assertDontSee('id="promo"', false);
});

it('renders contact, hours and benefits from the store settings', function () {
    StoreSetting::current()->update([
        'whatsapp' => '69999990000', 'address' => 'Av. Marechal Rondon, 100', 'hours_weekdays' => '9h às 18h', 'hours_saturday' => '9h às 13h',
        'plating_warranty' => '6 meses', 'pix_discount_percent' => 10, 'max_installments' => 6, 'cnpj' => '00.000.000/0001-00',
    ]);

    Livewire::test(Home::class)
        ->assertSee('(69) 99999-0000')->assertSee('Av. Marechal Rondon, 100')->assertSee('Seg a sex · 9h às 18h')->assertSee('Sábado · 9h às 13h')
        ->assertSee('Garantia de 6 meses')->assertSee('10% off no Pix')->assertSee('Ou em até 6x sem juros')->assertSee('CNPJ 00.000.000/0001-00');
});

it('offers the gift help form with the allowed options', function () {
    Livewire::test(Home::class)->assertSee('Ajuda para presente')->assertSee('Mãe')->assertSee('Namorada')->assertSee('Até R$ 100')->assertSee('Acima de R$ 200')->assertSee('Só porque sim');
});

it('renders the attendance shortcuts and the welcome message from the settings', function () {
    StoreSetting::current()->update(['whatsapp' => '69999990000', 'welcome_message' => 'Oi! Bem-vinda 💛']);

    $response = $this->get('/');

    $response->assertOk()->assertSee('Oi! Bem-vinda 💛')->assertSee('Ajuda para escolher um presente')->assertSee('Entrega e envio para outras cidades')->assertSee('Outra dúvida')
        ->assertSee('Atendimento STyli Prates')->assertSee('A gente responde rapidinho pelo WhatsApp');
});

it('does not use attendant names, online status or response time promises', function () {
    $html = $this->get('/')->getContent();

    expect(mb_strtolower($html))->not->toContain('online')->not->toContain('em até 5 minutos')->not->toContain('responde em');
});

it('renders the shortcuts in the configured order with the gift shortcut as an anchor action', function () {
    StoreSetting::current()->chatShortcuts()->delete();
    ChatShortcut::create(['store_setting_id' => StoreSetting::current()->id, 'label' => 'Segundo atalho', 'whatsapp_message' => 'Oi! Dois', 'position' => 2]);
    ChatShortcut::create(['store_setting_id' => StoreSetting::current()->id, 'label' => 'Primeiro atalho', 'whatsapp_message' => null, 'opens_gift_section' => true, 'position' => 1]);
    StoreSetting::current()->update(['whatsapp' => '69999990000']);

    $html = $this->get('/')->getContent();

    expect(strpos($html, 'Primeiro atalho'))->toBeLessThan(strpos($html, 'Segundo atalho'))
        ->and($html)->toContain('https://wa.me/5569999990000?text=Oi%21%20Dois');
});

it('shows the full care guide content and warranty from the settings', function () {
    StoreSetting::current()->update(['plating_warranty' => '6 meses']);

    $this->get('/')->assertSee('No dia a dia')->assertSee('O que evitar')->assertSee('Como limpar')->assertSee('Como guardar')->assertSee('Brilho que muda com o tempo')->assertSee('garantia de 6 meses no banho');
});

it('renders the help invitation with the three actions', function () {
    $this->get('/')->assertSee('Ficou na dúvida?')->assertSee('Falar com a atendente')->assertSee('É para presente')->assertSee('Agora não, obrigada');
});

it('does not render the strip of phrases under the hero', function () {
    $this->get('/')->assertDontSee('Feito para presentear')->assertDontSee('Entrega rápida')->assertDontSee('class="marquee"', false);
});

it('shows the default cover image-only until a title is set, then overlays the copy', function () {
    $this->get('/')->assertSee('feature--image', false)->assertSee('images/banner-capa.webp', false)->assertDontSee('feature-copy', false);

    StoreSetting::current()->update(['banner_title' => 'Conheça os conjuntos', 'banner_text' => 'Colar e brincos que combinam', 'banner_button' => 'Ver conjuntos', 'banner_eyebrow' => 'Coleção nova']);
    $this->get('/')->assertSee('Conheça os conjuntos')->assertSee('Colar e brincos que combinam')->assertSee('Ver conjuntos')->assertSee('Coleção nova')->assertSee('feature-copy', false);
});

it('hides the wide banner when the store turns it off', function () {
    StoreSetting::current()->update(['banner_enabled' => false]);

    $this->get('/')->assertDontSee('images/banner-capa.webp', false);
});

it('shows the banner photo and falls back to the button label "Ver peças"', function () {
    StoreSetting::current()->update(['banner_enabled' => true, 'banner_title' => 'Destaque', 'banner_photo_path' => 'banner/foto.webp']);

    $this->get('/')->assertSee('feature-photo', false)->assertSee('banner/foto.webp', false)->assertSee('Ver peças');
});

it('points the banner button to a category, promotions or the gift help', function () {
    StoreSetting::current()->update(['banner_enabled' => true, 'banner_title' => 'Destaque', 'banner_link' => (string) $this->colares->id]);
    $this->get('/')->assertSee("selectCategory('{$this->colares->id}')", false);

    StoreSetting::current()->update(['banner_link' => 'sale']);
    $this->get('/')->assertSee("selectCategory('sale')", false);

    StoreSetting::current()->update(['banner_link' => 'gift']);
    $this->get('/')->assertSee("getElementById('gift')", false);
});

it('falls back to all pieces when the banner category was deleted', function () {
    $temp = Category::factory()->create();
    StoreSetting::current()->update(['banner_enabled' => true, 'banner_title' => 'Destaque', 'banner_link' => (string) $temp->id]);
    $temp->delete();

    $this->get('/')->assertOk()->assertSee("selectCategory('all')", false);
});
