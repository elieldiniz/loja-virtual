<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Material;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    /** PNG 1x1 usado como foto de teste. */
    public const PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'material_id' => Material::factory(),
            'name' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 29.9, 399.9),
            'old_price' => null,
            'is_active' => false,
            'is_best_seller' => false,
            'is_new_release' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function bestSeller(): static
    {
        return $this->state(['is_best_seller' => true]);
    }

    public function newRelease(): static
    {
        return $this->state(['is_new_release' => true]);
    }

    public function withOldPrice(float $oldPrice): static
    {
        return $this->state(['old_price' => $oldPrice]);
    }

    public function withPhotos(int $count = 1): static
    {
        return $this->afterCreating(function (Product $product) use ($count) {
            for ($i = 1; $i <= $count; $i++) {
                $product->addMediaFromString(base64_decode(self::PIXEL_PNG))
                    ->usingFileName("foto-{$i}.png")
                    ->toMediaCollection(Product::PHOTOS);
            }
        });
    }
}
