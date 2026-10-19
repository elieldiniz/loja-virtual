<?php

namespace App\Livewire\Admin\Products;

use App\Models\Category;
use App\Models\Material;
use App\Models\Product;
use App\Services\PricingCalculator;
use App\Support\Money;
use App\Support\ProductCardData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Layout('layouts.admin')]
class Form extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $name = '';

    public string $categoryId = '';

    public string $materialId = '';

    public string $price = '';

    public string $oldPrice = '';

    public string $description = '';

    public bool $isActive = false;

    public bool $isBestSeller = false;

    public bool $isNewRelease = false;

    /** Novos arquivos enviados pelo input (são movidos para $uploads). */
    public array $newFiles = [];

    /** Arquivos já enviados e ainda não gravados, indexados por token "u:<id>". */
    public array $uploads = [];

    /** Ordem das fotos: "m:<media id>" (já gravada) ou "u:<id>" (enviada agora). */
    public array $photoOrder = [];

    public array $errorsBag = [];

    public function mount(?Product $product = null): void
    {
        $this->materialId = (string) Material::query()->where('is_active', true)->orderBy('id')->value('id');
        $this->categoryId = (string) Category::query()->ordered()->value('id');

        if ($product?->exists) {
            $this->product = $product;
            $this->name = $product->name;
            $this->categoryId = (string) $product->category_id;
            $this->materialId = (string) $product->material_id;
            $this->price = $this->formatInput($product->price);
            $this->oldPrice = $product->old_price !== null ? $this->formatInput($product->old_price) : '';
            $this->description = (string) $product->description;
            $this->isActive = $product->is_active;
            $this->isBestSeller = $product->is_best_seller;
            $this->isNewRelease = $product->is_new_release;
            $this->photoOrder = $product->getMedia(Product::PHOTOS)->sortBy('order_column')->map(fn (Media $m) => 'm:'.$m->id)->values()->all();
        }
    }

    public function updatedNewFiles(): void
    {
        $this->validate(['newFiles.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192'], [
            'newFiles.*.image' => 'O arquivo precisa ser uma imagem.',
            'newFiles.*.mimes' => 'Use fotos JPG, PNG ou WebP.',
            'newFiles.*.max' => 'Cada foto pode ter no máximo 8 MB.',
        ]);

        foreach ($this->newFiles as $file) {
            $token = 'u:'.Str::random(8);
            $this->uploads[$token] = $file;
            $this->photoOrder[] = $token;
        }

        $this->newFiles = [];
        unset($this->errorsBag['photos']);
    }

    public function movePhoto(int $index, int $direction): void
    {
        $target = $index + $direction;

        if (! isset($this->photoOrder[$index], $this->photoOrder[$target])) {
            return;
        }

        [$this->photoOrder[$index], $this->photoOrder[$target]] = [$this->photoOrder[$target], $this->photoOrder[$index]];
    }

    public function removePhoto(int $index): void
    {
        $token = $this->photoOrder[$index] ?? null;

        if ($token === null) {
            return;
        }

        unset($this->uploads[$token]);
        array_splice($this->photoOrder, $index, 1);
    }

    public function save()
    {
        $this->errorsBag = [];

        $name = trim($this->name);
        $price = Money::parse($this->price);
        $oldPrice = Money::parse($this->oldPrice);

        if ($name === '') {
            $this->errorsBag['name'] = 'Informe o nome da peça.';
        }

        if ($price === null || $price->amount() <= 0) {
            $this->errorsBag['price'] = 'Informe o preço de venda.';
        }

        if ($this->isActive && count($this->photoOrder) === 0) {
            $this->errorsBag['photos'] = 'Adicione pelo menos uma foto para mostrar a peça no site.';
        }

        if (! Category::whereKey((int) $this->categoryId)->exists()) {
            $this->errorsBag['category'] = 'Escolha uma categoria.';
        }

        if ($this->errorsBag !== []) {
            return;
        }

        // Preço antigo só vale quando maior que o preço de venda.
        $oldValue = ($oldPrice !== null && $oldPrice->isGreaterThan($price)) ? $oldPrice->toDecimal() : null;

        DB::transaction(function () use ($name, $price, $oldValue) {
            $product = $this->product ?? new Product;
            $product->fill([
                'category_id' => (int) $this->categoryId,
                'material_id' => (int) $this->materialId,
                'name' => $name,
                'description' => trim($this->description) ?: null,
                'price' => $price->toDecimal(),
                'old_price' => $oldValue,
                'is_active' => $this->isActive,
                'is_best_seller' => $this->isBestSeller,
                'is_new_release' => $this->isNewRelease,
            ])->save();

            $this->syncPhotos($product);
            $this->product = $product;
        });

        session()->flash('status', 'Peça salva');

        return $this->redirectRoute('admin.products', navigate: false);
    }

    public function delete()
    {
        if ($this->product?->exists) {
            $this->product->clearMediaCollection(Product::PHOTOS);
            $this->product->delete();
        }

        return $this->redirectRoute('admin.products');
    }

    private function syncPhotos(Product $product): void
    {
        $keep = collect($this->photoOrder)->filter(fn ($t) => str_starts_with($t, 'm:'))->map(fn ($t) => (int) substr($t, 2));

        $product->getMedia(Product::PHOTOS)
            ->reject(fn (Media $m) => $keep->contains($m->id))
            ->each->delete();

        $ids = [];

        foreach ($this->photoOrder as $token) {
            if (str_starts_with($token, 'm:')) {
                $ids[] = (int) substr($token, 2);

                continue;
            }

            $file = $this->uploads[$token] ?? null;

            if ($file !== null) {
                $ids[] = $product->addMedia($file->getRealPath())
                    ->usingFileName(Str::random(12).'.'.$file->getClientOriginalExtension())
                    ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                    ->toMediaCollection(Product::PHOTOS)->id;
            }
        }

        Media::setNewOrder($ids);
    }

    private function formatInput(string|float|null $value): string
    {
        return $value === null ? '' : number_format((float) $value, 2, ',', '');
    }

    /** @return list<array{token: string, url: ?string}> */
    private function photoItems(): array
    {
        $media = $this->product?->getMedia(Product::PHOTOS)->keyBy('id') ?? collect();

        return collect($this->photoOrder)->map(function (string $token) use ($media) {
            if (str_starts_with($token, 'm:')) {
                $item = $media->get((int) substr($token, 2));

                return ['token' => $token, 'url' => $item?->getUrl('thumb')];
            }

            try {
                return ['token' => $token, 'url' => $this->uploads[$token]->temporaryUrl()];
            } catch (\Throwable) {
                return ['token' => $token, 'url' => null];
            }
        })->all();
    }

    public function render()
    {
        $price = Money::parse($this->price) ?? Money::zero();
        $photos = $this->photoItems();

        $preview = ProductCardData::make(
            PricingCalculator::forStore(),
            id: null,
            name: trim($this->name) ?: 'Nome da peça',
            categoryName: null,
            price: $price,
            oldPrice: Money::parse($this->oldPrice),
            isNewRelease: $this->isNewRelease,
            coverUrl: $photos[0]['url'] ?? null,
        );

        return view('livewire.admin.products.form', [
            'categories' => Category::query()->ordered()->get(),
            'materials' => Material::query()->where('is_active', true)->orderBy('id')->get(),
            'photos' => $photos,
            'preview' => $preview,
            'isEditing' => $this->product?->exists === true,
        ])->layoutData(['current' => 'items', 'title' => $this->product?->exists ? 'Editar peça' : 'Nova peça']);
    }
}
