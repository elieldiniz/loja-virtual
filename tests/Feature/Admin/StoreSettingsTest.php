<?php

use App\Livewire\Admin\StoreSettings;
use App\Models\Category;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\PricingCalculator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    $this->actingAs(User::factory()->create());
});

it('saves contact data normalizing the whatsapp number', function () {
    Livewire::test(StoreSettings::class)
        ->set('whatsapp', '(69) 99999-0000')->set('instagram', '@styliprates')->set('city', 'Ji-Paraná')
        ->set('hoursWeekdays', '9h às 18h')->call('save')->assertHasNoErrors();

    $s = StoreSetting::current();
    expect($s->whatsapp)->toBe('69999990000')->and($s->instagram)->toBe('@styliprates')->and($s->hours_weekdays)->toBe('9h às 18h');
});

it('rejects an invalid whatsapp', function () {
    Livewire::test(StoreSettings::class)->set('whatsapp', '12345')->call('save')->assertHasErrors('whatsapp');
});

it('validates payment limits', function (string $field, string $value, bool $valid) {
    $component = Livewire::test(StoreSettings::class)->set($field, $value)->call('save');

    $valid ? $component->assertHasNoErrors($field) : $component->assertHasErrors($field);
})->with([
    ['pixDiscountPercent', '31', false], ['pixDiscountPercent', '30', true], ['pixDiscountPercent', '0', true], ['pixDiscountPercent', '-1', false],
    ['maxInstallments', '0', false], ['maxInstallments', '13', false], ['maxInstallments', '12', true], ['maxInstallments', '1', true],
    ['birthdayGiftPercent', '51', false], ['birthdayGiftPercent', '50', true],
    ['helpInviteMinutes', '4', false], ['helpInviteMinutes', '5', true],
]);

it('applies the new Pix discount immediately after saving', function () {
    Livewire::test(StoreSettings::class)->set('pixDiscountPercent', '10')->set('maxInstallments', '6')->call('save');

    $pricing = PricingCalculator::forStore();
    expect($pricing->pixDiscountPercent)->toBe(10)->and($pricing->maxInstallments)->toBe(6);
});

it('discards empty promo phrases and keeps the order', function () {
    Livewire::test(StoreSettings::class)
        ->set('promoPhrases', ['Primeira', '  ', 'Segunda', ''])->call('save');

    expect(StoreSetting::current()->promoPhrases->pluck('phrase')->all())->toBe(['Primeira', 'Segunda']);
});

it('saves shortcuts, discarding empty ones, and the gift shortcut without message', function () {
    Livewire::test(StoreSettings::class)->set('shortcuts', [
        ['label' => 'Ajuda para escolher um presente', 'message' => 'ignorada', 'gift' => true],
        ['label' => '', 'message' => 'sem rótulo', 'gift' => false],
        ['label' => 'Entrega para outras cidades', 'message' => 'Oi! Como funciona a entrega?', 'gift' => false],
    ])->call('save');

    $shortcuts = StoreSetting::current()->chatShortcuts;

    expect($shortcuts)->toHaveCount(2)
        ->and($shortcuts[0]->opens_gift_section)->toBeTrue()
        ->and($shortcuts[0]->whatsapp_message)->toBeNull()
        ->and($shortcuts[1]->whatsapp_message)->toBe('Oi! Como funciona a entrega?')
        ->and($shortcuts->pluck('position')->all())->toBe([1, 2]);
});

it('uploads the chat button photo and reverts to the logo', function () {
    Livewire::test(StoreSettings::class)
        ->set('buttonMode', 'photo')->set('buttonPhoto', UploadedFile::fake()->image('loja.jpg', 300, 300))->call('save');

    $path = StoreSetting::current()->chat_button_photo_path;
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);

    Livewire::test(StoreSettings::class)->set('buttonMode', 'logo')->call('save');

    expect(StoreSetting::current()->chat_button_photo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('rejects a chat button file that is not an image', function () {
    Livewire::test(StoreSettings::class)
        ->set('buttonMode', 'photo')->set('buttonPhoto', UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'))->call('save')
        ->assertHasErrors('buttonPhoto');
});

it('saves the wide banner text, button and destination', function () {
    $category = Category::factory()->create();

    Livewire::test(StoreSettings::class)
        ->set('bannerEnabled', true)->set('bannerEyebrow', 'Coleção nova')->set('bannerTitle', 'Conheça os conjuntos')
        ->set('bannerText', 'Colar e brincos que combinam')->set('bannerButton', 'Ver conjuntos')->set('bannerLink', (string) $category->id)
        ->call('save')->assertHasNoErrors();

    $s = StoreSetting::current();
    expect($s->banner_enabled)->toBeTrue()->and($s->banner_title)->toBe('Conheça os conjuntos')->and($s->banner_button)->toBe('Ver conjuntos')
        ->and($s->banner_link)->toBe((string) $category->id)->and($s->hasBanner())->toBeTrue();
});

it('is active with the switch on and either a title or a photo (default cover counts)', function () {
    Livewire::test(StoreSettings::class)->set('bannerEnabled', true)->set('bannerTitle', '')->call('save');
    $s = StoreSetting::current();

    expect($s->hasBanner())->toBeTrue()->and($s->bannerPhotoUrl())->toContain('images/banner-capa.webp');

    Livewire::test(StoreSettings::class)->set('bannerEnabled', false)->call('save');

    expect(StoreSetting::current()->hasBanner())->toBeFalse();
});

it('validates the banner limits and destination', function (string $field, string $value) {
    Livewire::test(StoreSettings::class)->set($field, $value)->call('save')->assertHasErrors($field);
})->with([
    ['bannerTitle', str_repeat('a', 81)],
    ['bannerText', str_repeat('a', 161)],
    ['bannerButton', str_repeat('a', 31)],
    ['bannerLink', 'qualquer-coisa'],
    ['bannerLink', '99999'],
]);

it('resizes and converts the banner photo to webp and removes the old file when replaced', function () {
    Livewire::test(StoreSettings::class)
        ->set('bannerEnabled', true)->set('bannerTitle', 'Destaque')
        ->set('bannerPhoto', UploadedFile::fake()->image('banner.jpg', 3000, 1000))->call('save')->assertHasNoErrors();

    $first = StoreSetting::current()->banner_photo_path;
    expect($first)->toStartWith('banner/')->toEndWith('.webp');
    Storage::disk('public')->assertExists($first);
    expect(getimagesizefromstring(Storage::disk('public')->get($first))[0])->toBe(1800);

    Livewire::test(StoreSettings::class)->set('bannerPhoto', UploadedFile::fake()->image('outra.png', 1200, 400))->call('save');

    $second = StoreSetting::current()->banner_photo_path;
    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
});

it('removes the banner photo', function () {
    Livewire::test(StoreSettings::class)->set('bannerPhoto', UploadedFile::fake()->image('banner.jpg', 1600, 600))->call('save');
    $path = StoreSetting::current()->banner_photo_path;

    Livewire::test(StoreSettings::class)->call('removeBannerPhoto')->call('save');

    expect(StoreSetting::current()->banner_photo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('rejects a banner file that is not an image', function () {
    Livewire::test(StoreSettings::class)->set('bannerPhoto', UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'))->call('save')->assertHasErrors('bannerPhoto');
});
