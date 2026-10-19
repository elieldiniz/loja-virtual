<div>
    <div class="page-head">
        <div>
            <h1>Produtos</h1>
            <p>{{ $onSite }} no site · {{ $all - $onSite }} {{ ($all - $onSite) === 1 ? 'oculto' : 'ocultos' }}</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.products.create') }}"><x-icon name="plus" />Nova peça</a>
    </div>

    <div class="stats">
        <div class="card stat"><b>{{ $all }}</b><span>Peças cadastradas</span></div>
        <div class="card stat"><b>{{ $releases }}</b><span>Lançamentos</span></div>
        <div class="card stat"><b>{{ $onSale }}</b><span>Em promoção</span></div>
        <div class="card stat"><b>{{ $categoryCount }}</b><span>Categorias</span></div>
    </div>

    <div class="card toolbar">
        <div class="field-inline"><x-icon name="search" /><input class="input" type="search" wire:model.live.debounce.250ms="search" placeholder="Buscar peça" aria-label="Buscar peça"></div>
        <select class="select" wire:model.live="categoryId" aria-label="Filtrar por categoria">
            <option value="">Todas as categorias</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="card">
        @forelse ($products as $product)
            @php
                $photos = $product->getMedia(\App\Models\Product::PHOTOS);
                $hasSale = $product->hasValidOldPrice();
            @endphp
            <div class="row" wire:key="product-{{ $product->id }}">
                <div class="thumb">
                    @if ($photos->first())<img src="{{ $photos->sortBy('order_column')->first()->getUrl('thumb') }}" alt="">@endif
                </div>
                <div>
                    <div class="row-title">{{ $product->name }}</div>
                    <div class="row-sub">{{ $product->category->name }} · {{ $product->material->name }} · {{ $photos->count() }} {{ $photos->count() === 1 ? 'foto' : 'fotos' }}</div>
                    <div class="row-meta">
                        @if ($product->is_best_seller)<span class="pill">Mais vendido</span>@endif
                        @if ($product->is_new_release)<span class="pill new">Lançamento</span>@endif
                        @if ($hasSale)<span class="pill sale">Promoção</span>@endif
                        @unless ($product->is_active)<span class="pill off">Fora do site</span>@endunless
                    </div>
                </div>
                <div class="row-price">
                    @if ($hasSale)<s>{{ \App\Support\Money::from($product->old_price)->format() }}</s>@endif
                    <b>{{ \App\Support\Money::from($product->price)->format() }}</b>
                </div>
                <div class="row-actions">
                    <label class="switch">
                        <input type="checkbox" wire:click="toggle({{ $product->id }})" @checked($product->is_active)>
                        <span class="track"></span><span>{{ $product->is_active ? 'No site' : 'Oculto' }}</span>
                    </label>
                    <span style="display:flex;gap:8px">
                        <a class="icon-btn" href="{{ route('admin.products.edit', $product) }}" aria-label="Editar {{ $product->name }}"><x-icon name="edit" /></a>
                        <button class="icon-btn" wire:click="confirmDelete({{ $product->id }})" aria-label="Excluir {{ $product->name }}"><x-icon name="trash" /></button>
                    </span>
                </div>
            </div>
        @empty
            <div class="empty">Nenhuma peça encontrada.</div>
        @endforelse

        @if ($total > $products->count())
            <button class="more-btn" wire:click="showMore">Mostrar mais {{ min(\App\Livewire\Admin\Products\Index::PER_PAGE, $total - $products->count()) }} de {{ $total - $products->count() }} restantes</button>
        @endif
    </div>

    @if ($deleting)
        <div class="sheet-bg" wire:click.self="closeConfirm">
            <div class="sheet" role="alertdialog" aria-modal="true" aria-labelledby="cfH">
                <h3 id="cfH">Excluir {{ $deleting->name }}?</h3>
                <p>A peça sai do site e das listas. Vendas antigas continuam mostrando o nome e o preço da época. Isso não pode ser desfeito.</p>
                <div class="acts">
                    <button class="btn btn-ghost" wire:click="closeConfirm">Cancelar</button>
                    <button class="btn btn-primary" wire:click="delete">Excluir</button>
                </div>
            </div>
        </div>
    @endif
</div>
