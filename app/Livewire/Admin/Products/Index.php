<?php

namespace App\Livewire\Admin\Products;

use App\Models\Category;
use App\Models\Product;
use App\Support\SearchText;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public const PER_PAGE = 30;

    #[Url(as: 'busca')]
    public string $search = '';

    #[Url(as: 'categoria')]
    public string $categoryId = '';

    public int $limit = self::PER_PAGE;

    public ?int $confirmingDeleteId = null;

    public function mount(): void
    {
        if (session()->has('status')) {
            $this->dispatch('toast', message: session('status'));
        }
    }

    public function updatedSearch(): void
    {
        $this->limit = self::PER_PAGE;
    }

    public function updatedCategoryId(): void
    {
        $this->limit = self::PER_PAGE;
    }

    public function showMore(): void
    {
        $this->limit += self::PER_PAGE;
    }

    public function toggle(int $id): void
    {
        $product = Product::findOrFail($id);

        if (! $product->is_active && $product->getMedia(Product::PHOTOS)->isEmpty()) {
            $this->dispatch('toast', message: 'Adicione pelo menos uma foto antes de mostrar no site.', warn: true);

            return;
        }

        $product->update(['is_active' => ! $product->is_active]);

        $this->dispatch('toast', message: $product->is_active ? 'Peça agora está no site' : 'Peça oculta do site');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function closeConfirm(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        $product = Product::find($this->confirmingDeleteId);

        if ($product !== null) {
            $product->clearMediaCollection(Product::PHOTOS);
            $product->delete();
            $this->dispatch('toast', message: 'Peça excluída');
        }

        $this->confirmingDeleteId = null;
    }

    public function render()
    {
        $query = Product::query()
            ->with(['category', 'material', 'media'])
            ->when($this->categoryId !== '', fn ($q) => $q->where('category_id', (int) $this->categoryId))
            ->when(trim($this->search) !== '', function ($q) {
                $q->whereRaw(SearchText::sqlNormalized('products.name').' LIKE ?', ['%'.SearchText::normalize($this->search).'%']);
            })
            ->latest('id');

        $total = (clone $query)->count();

        return view('livewire.admin.products.index', [
            'products' => $query->limit($this->limit)->get(),
            'total' => $total,
            'categories' => Category::query()->ordered()->get(),
            'onSite' => Product::where('is_active', true)->count(),
            'all' => Product::count(),
            'releases' => Product::where('is_new_release', true)->count(),
            'onSale' => Product::whereNotNull('old_price')->whereColumn('old_price', '>', 'price')->count(),
            'categoryCount' => Category::count(),
            'deleting' => $this->confirmingDeleteId ? Product::find($this->confirmingDeleteId) : null,
        ])->layoutData(['current' => 'items', 'title' => 'Produtos']);
    }
}
