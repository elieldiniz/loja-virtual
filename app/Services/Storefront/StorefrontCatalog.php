<?php

namespace App\Services\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Support\SearchText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** Consultas da vitrine: só peças ativas e com pelo menos uma foto. */
class StorefrontCatalog
{
    public const NEW_RELEASES_LIMIT = 4;

    /** @return Builder<Product> */
    public function query(): Builder
    {
        return Product::query()->visible()->with(['category', 'material', 'media']);
    }

    /** @return Collection<int, Category> categorias na ordem do painel, com uma peça de capa para o círculo */
    public function categories(): Collection
    {
        return Category::query()->ordered()->get();
    }

    /** @return Collection<int, Product> */
    public function bestSellers(): Collection
    {
        return $this->query()->where('is_best_seller', true)->latest('id')->get();
    }

    /** @return Collection<int, Product> */
    public function newReleases(): Collection
    {
        return $this->query()->where('is_new_release', true)->latest('id')->limit(self::NEW_RELEASES_LIMIT)->get();
    }

    /** @return Collection<int, Product> peças em promoção (preço antigo maior que o preço de venda) */
    public function onSale(): Collection
    {
        return $this->query()->whereNotNull('old_price')->whereColumn('old_price', '>', 'price')->latest('id')->get();
    }

    /** @return Collection<int, Product> */
    public function inCategory(int $categoryId): Collection
    {
        return $this->query()->where('category_id', $categoryId)->latest('id')->get();
    }

    /** @return Collection<int, Product> busca por nome e categoria, sem diferenciar acentos */
    public function search(string $term): Collection
    {
        $text = SearchText::normalize($term);

        if ($text === '') {
            return new Collection;
        }

        return $this->query()
            ->where(function (Builder $q) use ($text) {
                $q->whereRaw(SearchText::sqlNormalized('products.name').' like ?', ['%'.$text.'%'])
                    ->orWhereHas('category', fn ($c) => $c->whereRaw(SearchText::sqlNormalized('categories.name').' like ?', ['%'.$text.'%']));
            })
            ->latest('id')->get();
    }

    /**
     * Peças visíveis para os ids pedidos (sacola e favoritos); ids inexistentes ou ocultos são ignorados.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Product>
     */
    public function byIds(array $ids): Collection
    {
        return $this->query()->whereIn('products.id', $ids)->get();
    }
}
