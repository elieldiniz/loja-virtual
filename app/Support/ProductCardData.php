<?php

namespace App\Support;

use App\Models\Product;
use App\Services\PricingCalculator;

/** Dados de apresentação de um card de peça (vitrine, prévia do painel e favoritos). */
final class ProductCardData
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly ?string $categoryName,
        public readonly Money $price,
        public readonly ?Money $oldPrice,
        public readonly Money $pixPrice,
        public readonly int $installments,
        public readonly Money $installmentAmount,
        public readonly ?string $badge,
        public readonly bool $isNewRelease,
        public readonly ?string $coverUrl = null,
        public readonly ?string $hoverUrl = null,
    ) {}

    public static function fromProduct(Product $product, PricingCalculator $pricing): self
    {
        $cover = $product->cover();
        $hover = $product->hoverPhoto();

        return self::make(
            $pricing,
            id: $product->id,
            name: $product->name,
            categoryName: $product->relationLoaded('category') ? $product->category?->name : null,
            price: Money::from($product->price),
            oldPrice: $product->old_price !== null ? Money::from($product->old_price) : null,
            isNewRelease: $product->is_new_release,
            coverUrl: $cover?->getUrl('card'),
            hoverUrl: $hover?->getUrl('card'),
        );
    }

    public static function make(
        PricingCalculator $pricing,
        ?int $id,
        string $name,
        ?string $categoryName,
        Money $price,
        ?Money $oldPrice,
        bool $isNewRelease,
        ?string $coverUrl = null,
        ?string $hoverUrl = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            categoryName: $categoryName,
            price: $price,
            oldPrice: $pricing->validOldPrice($price, $oldPrice),
            pixPrice: $pricing->pixPrice($price),
            installments: $pricing->maxInstallments,
            installmentAmount: $pricing->installmentAmount($price),
            badge: $pricing->badge($price, $oldPrice, $isNewRelease),
            isNewRelease: $isNewRelease,
            coverUrl: $coverUrl,
            hoverUrl: $hoverUrl,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->categoryName,
            'price' => $this->price->format(),
            'old_price' => $this->oldPrice?->format(),
            'pix_price' => $this->pixPrice->format(),
            'installments' => $this->installments,
            'installment_amount' => $this->installmentAmount->format(),
            'badge' => $this->badge,
            'cover_url' => $this->coverUrl,
            'hover_url' => $this->hoverUrl,
        ];
    }
}
