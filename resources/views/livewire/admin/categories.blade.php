<div>
    <div class="page-head"><div><h1>Categorias</h1><p>São os círculos e o menu do site, nesta ordem.</p></div></div>

    <div class="card">
        @forelse ($categories as $i => $category)
            <div class="cat-row" wire:key="cat-{{ $category->id }}">
                <div class="cat-order">
                    <button class="icon-btn" wire:click="move({{ $category->id }}, -1)" aria-label="Subir {{ $category->name }}" @disabled($i === 0)><x-icon name="up" /></button>
                    <button class="icon-btn" wire:click="move({{ $category->id }}, 1)" aria-label="Descer {{ $category->name }}" @disabled($i === $categories->count() - 1)><x-icon name="down" /></button>
                </div>
                <div class="cat-ico"><x-art :name="\App\Support\CategoryArt::key($category->name)" /></div>

                @if ($editingId === $category->id)
                    <form wire:submit="saveEdit" style="display:flex;gap:8px;flex-wrap:wrap">
                        <input class="input" wire:model="editName" aria-label="Nome da categoria" style="flex:1 1 140px" autofocus>
                        <button class="btn btn-primary" type="submit">Salvar</button>
                        <button class="btn btn-ghost" type="button" wire:click="cancelEdit">Cancelar</button>
                        @if ($error)<span class="err" style="flex-basis:100%">{{ $error }}</span>@endif
                    </form>
                    <span></span>
                @else
                    <div>
                        <div class="cat-name">{{ $category->name }}</div>
                        <div class="cat-count">{{ $category->products_count }} {{ $category->products_count === 1 ? 'peça' : 'peças' }}</div>
                    </div>
                    <button class="icon-btn" wire:click="startEdit({{ $category->id }})" aria-label="Renomear {{ $category->name }}"><x-icon name="edit" /></button>
                @endif

                <button class="icon-btn" wire:click="confirmDelete({{ $category->id }})" aria-label="Excluir {{ $category->name }}"><x-icon name="trash" /></button>
            </div>
        @empty
            <div class="empty">Nenhuma categoria ainda.</div>
        @endforelse

        <form class="add-cat" wire:submit="add">
            <input class="input" wire:model="newName" placeholder="Nova categoria, ex.: Tornozeleiras" aria-label="Nome da nova categoria">
            <button class="btn btn-primary" type="submit"><x-icon name="plus" />Adicionar</button>
            @if ($error && $editingId === null)<span class="err" style="flex-basis:100%">{{ $error }}</span>@endif
        </form>
    </div>

    @if ($deleting)
        <div class="sheet-bg" wire:click.self="closeConfirm">
            <div class="sheet" role="alertdialog" aria-modal="true" aria-labelledby="cfH">
                @if ($deleting->products_count > 0)
                    <h3 id="cfH">Não dá para excluir {{ $deleting->name }}</h3>
                    <p>Essa categoria tem {{ $deleting->products_count }} {{ $deleting->products_count === 1 ? 'peça' : 'peças' }}. Mova {{ $deleting->products_count === 1 ? 'a peça' : 'as peças' }} para outra categoria antes de excluir.</p>
                    <div class="acts"><button class="btn btn-primary" wire:click="closeConfirm">Entendi</button></div>
                @else
                    <h3 id="cfH">Excluir {{ $deleting->name }}?</h3>
                    <p>Essa categoria está vazia e sairá do menu do site. Isso não pode ser desfeito.</p>
                    <div class="acts">
                        <button class="btn btn-ghost" wire:click="closeConfirm">Cancelar</button>
                        <button class="btn btn-primary" wire:click="delete">Excluir</button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
