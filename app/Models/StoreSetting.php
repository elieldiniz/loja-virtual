<?php

namespace App\Models;

use Database\Factories\StoreSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

#[Fillable(['whatsapp', 'instagram', 'address', 'city', 'state', 'cnpj', 'hours_weekdays', 'hours_saturday', 'hours_sunday', 'pix_discount_percent', 'max_installments', 'plating_warranty', 'welcome_message', 'chat_button_photo_path', 'help_invite_enabled', 'help_invite_minutes', 'birthday_gift_percent', 'banner_enabled', 'banner_eyebrow', 'banner_title', 'banner_text', 'banner_button', 'banner_link', 'banner_photo_path'])]
class StoreSetting extends Model
{
    /** @use HasFactory<StoreSettingFactory> */
    use HasFactory;

    public const CACHE_KEY = 'store_setting.current.v2';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Linha única de configurações; cria com os defaults do banco se ainda não existir.
     * Guarda no cache apenas os atributos (o Laravel não desserializa models do cache).
     */
    public static function current(): self
    {
        $attributes = Cache::rememberForever(self::CACHE_KEY, function () {
            $setting = static::query()->orderBy('id')->first() ?? static::create([]);

            return ($setting->fresh() ?? $setting)->getAttributes();
        });

        return (new static)->newFromBuilder($attributes);
    }

    public function chatButtonPhotoUrl(): ?string
    {
        return $this->chat_button_photo_path
            ? Storage::disk(config('media-library.disk_name'))->url($this->chat_button_photo_path)
            : null;
    }

    public const DEFAULT_BANNER = 'images/banner-capa.webp';

    /** Foto enviada no painel; sem ela, vale a capa padrão da STyli Prates (public/images/banner-capa.webp). */
    public function bannerPhotoUrl(): ?string
    {
        if ($this->banner_photo_path) {
            return Storage::disk(config('media-library.disk_name'))->url($this->banner_photo_path);
        }

        return file_exists(public_path(self::DEFAULT_BANNER)) ? asset(self::DEFAULT_BANNER) : null;
    }

    /** Banner ligado e com título ou foto. Sem título, a foto (que já traz o texto) aparece sozinha. */
    public function hasBanner(): bool
    {
        return $this->banner_enabled && (filled($this->banner_title) || $this->bannerPhotoUrl() !== null);
    }

    protected function casts(): array
    {
        return [
            'pix_discount_percent' => 'integer',
            'max_installments' => 'integer',
            'help_invite_enabled' => 'boolean',
            'help_invite_minutes' => 'integer',
            'birthday_gift_percent' => 'integer',
            'banner_enabled' => 'boolean',
        ];
    }

    /** @return HasMany<PromoPhrase, $this> */
    public function promoPhrases(): HasMany
    {
        return $this->hasMany(PromoPhrase::class)->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<ChatShortcut, $this> */
    public function chatShortcuts(): HasMany
    {
        return $this->hasMany(ChatShortcut::class)->orderBy('position')->orderBy('id');
    }
}
