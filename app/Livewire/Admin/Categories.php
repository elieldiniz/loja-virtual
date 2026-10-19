<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Support\SearchText;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Categories extends Component
{
    public string $newName = '';

    public ?int $editingId = null;

    public string $editName = '';

    public ?int $confirmingDeleteId = null;

    public string $error = '';

    public function add(): void
    {
        $this->error = '';
        $name = trim($this->newName);

        if (($message = $this->nameError($name)) !== null) {
            $this->error = $message;

            return;
        }

        Category::create(['name' => $name, 'position' => (Category::max('position') ?? 0) + 1]);

        $this->newName = '';
        $this->dispatch('toast', message: 'Categoria adicionada');
    }

    public function startEdit(int $id): void
    {
        $this->error = '';
        $this->editingId = $id;
        $this->editName = Category::findOrFail($id)->name;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->error = '';
    }

    public function saveEdit(): void
    {
        $category = Category::findOrFail($this->editingId);
        $name = trim($this->editName);

        if (($message = $this->nameError($name, $category->id)) !== null) {
            $this->error = $message;

            return;
        }

        $category->update(['name' => $name]);

        $this->editingId = null;
        $this->error = '';
        $this->dispatch('toast', message: 'Categoria renomeada');
    }

    public function move(int $id, int $direction): void
    {
        $ordered = Category::query()->ordered()->get()->values();
        $index = $ordered->search(fn (Category $c) => $c->id === $id);
        $target = $index + ($direction < 0 ? -1 : 1);

        if ($index === false || $target < 0 || $target >= $ordered->count()) {
            return;
        }

        $list = $ordered->all();
        [$list[$index], $list[$target]] = [$list[$target], $list[$index]];

        foreach ($list as $position => $category) {
            if ($category->position !== $position + 1) {
                $category->update(['position' => $position + 1]);
            }
        }
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
        $category = Category::withCount('products')->find($this->confirmingDeleteId);

        if ($category === null || $category->products_count > 0) {
            return;
        }

        $category->delete();

        $this->confirmingDeleteId = null;
        $this->dispatch('toast', message: 'Categoria excluída');
    }

    private function nameError(string $name, ?int $ignoreId = null): ?string
    {
        if ($name === '') {
            return 'Informe o nome da categoria.';
        }

        $exists = Category::query()
            ->where('name_normalized', SearchText::normalize($name))
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        return $exists ? 'Já existe uma categoria com esse nome.' : null;
    }

    public function render()
    {
        $categories = Category::query()->ordered()->withCount('products')->get();

        return view('livewire.admin.categories', [
            'categories' => $categories,
            'deleting' => $this->confirmingDeleteId ? $categories->firstWhere('id', $this->confirmingDeleteId) : null,
        ])->layoutData(['current' => 'cats', 'title' => 'Categorias']);
    }
}
