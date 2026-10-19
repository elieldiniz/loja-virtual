<?php

namespace App\Models;

use Database\Factories\StoreSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

#[Fillable(['whatsapp', 'instagram', 'address', 'city', 'state', 'cnpj', 'hours_weekdays', 'hours_saturday', 'hours_sunday', 'pix_discount_percent', 'max_installments', 'plating_warranty', 'welcome_message', 'chat_button_photo_path', 'help_invite_enabled', 'help_invite_minutes', 'birthday_gift_percent'])]
class StoreSetting extends Model
{
    /** @use HasFactory<StoreSettingFactory> */
    use HasFactory;

    public const CACHE_KEY = 'store_setting.current';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /** Linha única de configurações; cria com os defaults do banco se ainda não existir. */
    public static function current(): self
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $setting = static::query()->orderBy('id')->first() ?? static::create([]);

            return $setting->fresh() ?? $setting;
        });
    }

    public function chatButtonPhotoUrl(): ?string
    {
        return $this->chat_button_photo_path
            ? Storage::disk(config('media-library.disk_name'))->url($this->chat_button_photo_path)
            : null;
    }

    protected function casts(): array
    {
        return [
            'pix_discount_percent' => 'integer',
            'max_installments' => 'integer',
            'help_invite_enabled' => 'boolean',
            'help_invite_minutes' => 'integer',
            'birthday_gift_percent' => 'integer',
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
