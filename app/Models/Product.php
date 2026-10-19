<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['category_id', 'material_id', 'name', 'description', 'price', 'old_price', 'is_active', 'is_best_seller', 'is_new_release'])]
class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use InteractsWithMedia;

    public const PHOTOS = 'photos';

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_best_seller' => 'boolean',
            'is_new_release' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PHOTOS);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 640, 640)
            ->format('webp')
            ->performOnCollections(self::PHOTOS);

        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 160, 160)
            ->format('webp')
            ->performOnCollections(self::PHOTOS);
    }

    /**
     * Peças aparecem na vitrine somente com "No site" ligado e ao menos uma foto.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereExists(function ($sub) {
            $sub->selectRaw('1')
                ->from('media')
                ->whereColumn('media.model_id', 'products.id')
                ->where('media.model_type', (new self)->getMorphClass())
                ->where('media.collection_name', self::PHOTOS);
        });
    }

    public function cover(): ?Media
    {
        return $this->getMedia(self::PHOTOS)->sortBy('order_column')->first();
    }

    public function hoverPhoto(): ?Media
    {
        return $this->getMedia(self::PHOTOS)->sortBy('order_column')->values()->get(1);
    }

    public function hasValidOldPrice(): bool
    {
        return $this->old_price !== null
            && (int) round((float) $this->old_price * 100) > (int) round((float) $this->price * 100);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Material, $this> */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /** @return HasMany<SaleItem, $this> */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
