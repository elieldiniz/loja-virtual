<?php

namespace App\Livewire\Storefront;

use App\Models\Category;
use App\Models\Material;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Services\MessageBuilder;
use App\Services\PricingCalculator;
use App\Services\Storefront\StorefrontCatalog;
use App\Services\Storefront\StorefrontLinks;
use App\Support\ProductCardData;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Home extends Component
{
    /** all | sale | id da categoria */
    #[Url(as: 'categoria', except: 'all')]
    public string $category = 'all';

    #[Url(as: 'material', except: '')]
    public string $material = '';

    #[Url(as: 'busca', except: '')]
    public string $q = '';

    /** @var list<int> favoritos do aparelho (enviados pelo navegador) quando a cliente abre "Favoritos" */
    public array $favoriteIds = [];

    public bool $showingFavorites = false;

    public function selectCategory(string $category): void
    {
        $this->category = $category === 'sale' || ctype_digit($category) ? $category : 'all';
        $this->material = '';
        $this->q = '';
        $this->showingFavorites = false;
    }

    public function selectMaterial(string $slug): void
    {
        $this->material = Material::where('slug', $slug)->exists() ? $slug : '';
        $this->category = 'all';
        $this->q = '';
        $this->showingFavorites = false;
    }

    public function updatedQ(): void
    {
        $this->category = 'all';
        $this->material = '';
        $this->showingFavorites = false;
    }

    public function showFavorites(array $ids): void
    {
        $this->favoriteIds = collect($ids)->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique()->take(60)->values()->all();
        $this->showingFavorites = true;
        $this->category = 'all';
        $this->material = '';
        $this->q = '';
    }

    public function clearFilters(): void
    {
        $this->category = 'all';
        $this->material = '';
        $this->q = '';
        $this->showingFavorites = false;
    }

    public function render(StorefrontCatalog $catalog, MessageBuilder $messages)
    {
        $settings = StoreSetting::current();
        $settings->load(['promoPhrases', 'chatShortcuts']);
        $pricing = PricingCalculator::forStore($settings);
        $links = new StorefrontLinks($settings);

        [$products, $title, $eyebrow, $filtered] = $this->shopSection($catalog);
        $cards = fn (Collection $list) => $list->map(fn (Product $p) => ProductCardData::fromProduct($p, $pricing));

        $categories = $catalog->categories();
        $cover = Product::query()->visible()->with('media')->whereIn('category_id', $categories->pluck('id'))->latest('id')->get()->groupBy('category_id');

        return view('livewire.storefront.home', [
            'settings' => $settings,
            'pricing' => $pricing,
            'links' => $links,
            'messages' => $messages,
            'categories' => $categories,
            'categoryCovers' => $categories->mapWithKeys(fn (Category $c) => [$c->id => $cover->get($c->id)?->first()?->cover()?->getUrl('thumb')]),
            'shopCards' => $cards($products),
            'newCards' => $cards($catalog->newReleases()),
            'shopTitle' => $title,
            'shopEyebrow' => $eyebrow,
            'filtered' => $filtered,
            'instagramTiles' => $cards(Product::query()->visible()->with(['media'])->latest('id')->limit(6)->get()),
        ])->title('STyli Prates');
    }

    /** @return array{0: Collection<int, Product>, 1: string, 2: string, 3: bool} */
    private function shopSection(StorefrontCatalog $catalog): array
    {
        if ($this->showingFavorites) {
            $list = $catalog->byIds($this->favoriteIds);
            $order = array_flip($this->favoriteIds);

            return [$list->sortBy(fn (Product $p) => $order[$p->id] ?? 0)->values(), 'Seus favoritos', 'Salvos neste aparelho', true];
        }

        if (trim($this->q) !== '') {
            $list = $catalog->search($this->q);

            return [$list, 'Busca: "'.trim($this->q).'"', $list->count().' '.($list->count() === 1 ? 'peça' : 'peças'), true];
        }

        if ($this->material !== '') {
            return [$catalog->inMaterial($this->material), Material::where('slug', $this->material)->value('name') ?? 'Material', 'Material', true];
        }

        if ($this->category === 'sale') {
            return [$catalog->onSale(), 'Promoções', 'Por tempo limitado', true];
        }

        if (ctype_digit($this->category)) {
            $category = Category::find((int) $this->category);

            return [$category ? $catalog->inCategory($category->id) : collect(), $category?->name ?? 'Categoria', 'Categoria', true];
        }

        return [$catalog->bestSellers(), 'Mais vendidos', 'Mais amadas', false];
    }
}
