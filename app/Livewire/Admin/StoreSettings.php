<?php

namespace App\Livewire\Admin;

use App\Models\StoreSetting;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class StoreSettings extends Component
{
    use WithFileUploads;

    public string $whatsapp = '';

    public string $instagram = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $cnpj = '';

    public string $hoursWeekdays = '';

    public string $hoursSaturday = '';

    public string $hoursSunday = '';

    public string $pixDiscountPercent = '5';

    public string $maxInstallments = '3';

    public string $platingWarranty = '';

    /** @var list<string> */
    public array $promoPhrases = [];

    public string $welcomeMessage = '';

    public string $birthdayGiftPercent = '10';

    public string $buttonMode = 'logo';

    public $buttonPhoto = null;

    public ?string $currentPhotoPath = null;

    /** @var list<array{label: string, message: string, gift: bool}> */
    public array $shortcuts = [];

    public bool $helpInviteEnabled = true;

    public string $helpInviteMinutes = '2';

    public function mount(): void
    {
        $s = StoreSetting::current();
        $s->load(['promoPhrases', 'chatShortcuts']);

        $this->whatsapp = (string) $s->whatsapp;
        $this->instagram = (string) $s->instagram;
        $this->address = (string) $s->address;
        $this->city = (string) $s->city;
        $this->state = (string) $s->state;
        $this->cnpj = (string) $s->cnpj;
        $this->hoursWeekdays = (string) $s->hours_weekdays;
        $this->hoursSaturday = (string) $s->hours_saturday;
        $this->hoursSunday = (string) $s->hours_sunday;
        $this->pixDiscountPercent = (string) $s->pix_discount_percent;
        $this->maxInstallments = (string) $s->max_installments;
        $this->platingWarranty = (string) $s->plating_warranty;
        $this->promoPhrases = $s->promoPhrases->pluck('phrase')->all();
        $this->welcomeMessage = (string) $s->welcome_message;
        $this->birthdayGiftPercent = (string) $s->birthday_gift_percent;
        $this->currentPhotoPath = $s->chat_button_photo_path;
        $this->buttonMode = $s->chat_button_photo_path ? 'photo' : 'logo';
        $this->shortcuts = $s->chatShortcuts->map(fn ($c) => [
            'label' => $c->label,
            'message' => (string) $c->whatsapp_message,
            'gift' => $c->opens_gift_section,
        ])->all();
        $this->helpInviteEnabled = $s->help_invite_enabled;
        $this->helpInviteMinutes = (string) $s->help_invite_minutes;
    }

    public function addPromo(): void
    {
        $this->promoPhrases[] = '';
    }

    public function removePromo(int $index): void
    {
        unset($this->promoPhrases[$index]);
        $this->promoPhrases = array_values($this->promoPhrases);
    }

    public function addShortcut(): void
    {
        $this->shortcuts[] = ['label' => '', 'message' => '', 'gift' => false];
    }

    public function removeShortcut(int $index): void
    {
        unset($this->shortcuts[$index]);
        $this->shortcuts = array_values($this->shortcuts);
    }

    public function removePhoto(): void
    {
        $this->buttonPhoto = null;
        $this->currentPhotoPath = null;
        $this->buttonMode = 'logo';
    }

    public function save(): void
    {
        $this->validate([
            'whatsapp' => ['nullable', 'string', 'max:30', function ($attribute, $value, $fail) {
                if (filled($value) && ! Phone::isValid($value)) {
                    $fail('Informe o WhatsApp com DDD (mínimo de 10 dígitos).');
                }
            }],
            'instagram' => 'nullable|string|max:80',
            'address' => 'nullable|string|max:160',
            'city' => 'nullable|string|max:80',
            'state' => 'nullable|string|max:40',
            'cnpj' => 'nullable|string|max:30',
            'hoursWeekdays' => 'nullable|string|max:80',
            'hoursSaturday' => 'nullable|string|max:80',
            'hoursSunday' => 'nullable|string|max:80',
            'pixDiscountPercent' => 'required|integer|between:0,30',
            'maxInstallments' => 'required|integer|between:1,12',
            'platingWarranty' => 'nullable|string|max:80',
            'welcomeMessage' => 'nullable|string|max:160',
            'birthdayGiftPercent' => 'required|integer|between:0,50',
            'helpInviteMinutes' => 'required|in:1,2,3,5',
            'buttonPhoto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'pixDiscountPercent.between' => 'O desconto no Pix deve ficar entre 0 e 30%.',
            'pixDiscountPercent.required' => 'Informe o desconto no Pix (pode ser 0).',
            'pixDiscountPercent.integer' => 'Use um número inteiro para o desconto no Pix.',
            'maxInstallments.between' => 'As parcelas sem juros devem ficar entre 1 e 12.',
            'maxInstallments.required' => 'Informe o número de parcelas.',
            'maxInstallments.integer' => 'Use um número inteiro para as parcelas.',
            'birthdayGiftPercent.between' => 'O presente de aniversário deve ficar entre 0 e 50%.',
            'birthdayGiftPercent.required' => 'Informe o presente de aniversário (pode ser 0).',
            'birthdayGiftPercent.integer' => 'Use um número inteiro para o presente de aniversário.',
            'helpInviteMinutes.in' => 'Escolha 1, 2, 3 ou 5 minutos.',
            'buttonPhoto.image' => 'Envie uma imagem.',
            'buttonPhoto.mimes' => 'Use uma foto JPG, PNG ou WebP.',
            'buttonPhoto.max' => 'A foto pode ter no máximo 5 MB.',
        ]);

        DB::transaction(function () {
            $setting = StoreSetting::current();
            $disk = Storage::disk(config('media-library.disk_name'));

            $photoPath = $this->currentPhotoPath;

            if ($this->buttonMode === 'logo') {
                $photoPath = null;
            } elseif ($this->buttonPhoto) {
                $photoPath = $this->buttonPhoto->store('loja', config('media-library.disk_name'));
            }

            if ($setting->chat_button_photo_path && $setting->chat_button_photo_path !== $photoPath) {
                $disk->delete($setting->chat_button_photo_path);
            }

            $setting->update([
                'whatsapp' => Phone::normalize($this->whatsapp) ?: null,
                'instagram' => $this->blank($this->instagram),
                'address' => $this->blank($this->address),
                'city' => $this->blank($this->city),
                'state' => $this->blank($this->state),
                'cnpj' => $this->blank($this->cnpj),
                'hours_weekdays' => $this->blank($this->hoursWeekdays),
                'hours_saturday' => $this->blank($this->hoursSaturday),
                'hours_sunday' => $this->blank($this->hoursSunday),
                'pix_discount_percent' => (int) $this->pixDiscountPercent,
                'max_installments' => (int) $this->maxInstallments,
                'plating_warranty' => $this->blank($this->platingWarranty),
                'welcome_message' => $this->blank($this->welcomeMessage),
                'chat_button_photo_path' => $photoPath,
                'help_invite_enabled' => $this->helpInviteEnabled,
                'help_invite_minutes' => (int) $this->helpInviteMinutes,
                'birthday_gift_percent' => (int) $this->birthdayGiftPercent,
            ]);

            $setting->promoPhrases()->delete();
            foreach (array_values(array_filter(array_map('trim', $this->promoPhrases), 'strlen')) as $position => $phrase) {
                $setting->promoPhrases()->create(['phrase' => $phrase, 'position' => $position + 1]);
            }

            $setting->chatShortcuts()->delete();
            $position = 0;
            foreach ($this->shortcuts as $shortcut) {
                $label = trim($shortcut['label'] ?? '');

                if ($label === '') {
                    continue;
                }

                $gift = (bool) ($shortcut['gift'] ?? false);
                $setting->chatShortcuts()->create([
                    'label' => $label,
                    'whatsapp_message' => $gift ? null : $this->blank($shortcut['message'] ?? ''),
                    'opens_gift_section' => $gift,
                    'position' => ++$position,
                ]);
            }

            $this->currentPhotoPath = $photoPath;
        });

        $this->buttonPhoto = null;
        $this->mount();
        $this->dispatch('toast', message: 'Configurações salvas');
    }

    private function blank(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function render()
    {
        $photoUrl = null;

        if ($this->buttonPhoto) {
            try {
                $photoUrl = $this->buttonPhoto->temporaryUrl();
            } catch (\Throwable) {
                $photoUrl = null;
            }
        } elseif ($this->currentPhotoPath) {
            $photoUrl = Storage::disk(config('media-library.disk_name'))->url($this->currentPhotoPath);
        }

        return view('livewire.admin.store-settings', ['photoUrl' => $photoUrl])
            ->layoutData(['current' => 'store', 'title' => 'Loja']);
    }
}
