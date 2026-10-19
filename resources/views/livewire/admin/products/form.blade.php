<div>
    <div class="page-head">
        <div>
            <h1>{{ $isEditing ? 'Editar peça' : 'Nova peça' }}</h1>
            <p>{{ $isEditing ? $product->name : 'Preencha os dados e adicione as fotos.' }}</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('admin.products') }}"><x-icon name="left" />Voltar</a>
    </div>

    <form wire:submit="save">
        <div class="form-grid">
            <div style="display:flex;flex-direction:column;gap:20px;min-width:0">
                <section class="card panel">
                    <h2>Fotos</h2>
                    <p class="hint">A primeira é a capa. A segunda aparece quando a cliente passa o mouse na peça.</p>
                    <div class="photos">
                        @foreach ($photos as $i => $photo)
                            <div class="ph" wire:key="photo-{{ $photo['token'] }}">
                                @if ($photo['url'])<img src="{{ $photo['url'] }}" alt="">@endif
                                @if ($i === 0)<span class="cover">Capa</span>@endif
                                <div class="ph-actions">
                                    <span style="display:flex;gap:4px">
                                        <button type="button" wire:click="movePhoto({{ $i }}, -1)" aria-label="Mover foto para a esquerda" @disabled($i === 0)><x-icon name="left" /></button>
                                        <button type="button" wire:click="movePhoto({{ $i }}, 1)" aria-label="Mover foto para a direita" @disabled($i === count($photos) - 1)><x-icon name="right" /></button>
                                    </span>
                                    <button type="button" wire:click="removePhoto({{ $i }})" aria-label="Remover foto"><x-icon name="x" /></button>
                                </div>
                            </div>
                        @endforeach
                        <label class="add-photo" for="phIn">
                            <x-icon name="camera" />
                            <span wire:loading.remove wire:target="newFiles">Adicionar fotos</span>
                            <span wire:loading wire:target="newFiles">Enviando…</span>
                            <input id="phIn" type="file" accept="image/*" multiple wire:model="newFiles">
                        </label>
                    </div>
                    @error('newFiles.*')<span class="err">{{ $message }}</span>@enderror
                    @isset($errorsBag['photos'])<span class="err">{{ $errorsBag['photos'] }}</span>@endisset
                </section>

                <section class="card panel">
                    <h2>Dados da peça</h2>
                    <div class="fields">
                        <label class="f full" for="fName">Nome
                            <input class="input" id="fName" wire:model.live.debounce.300ms="name" placeholder="Ex.: Colar Ponto de Luz">
                            @isset($errorsBag['name'])<span class="err">{{ $errorsBag['name'] }}</span>@endisset
                        </label>
                        <label class="f" for="fCat">Categoria
                            <select class="select" id="fCat" wire:model="categoryId">
                                @foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                            </select>
                            @isset($errorsBag['category'])<span class="err">{{ $errorsBag['category'] }}</span>@endisset
                        </label>
                        <div class="f" style="display:flex;flex-direction:column;gap:6px;font-weight:500;font-size:14px">Material
                            <div class="chips-choose">
                                @foreach ($materials as $material)
                                    <label class="chip-opt"><input type="radio" wire:model="materialId" value="{{ $material->id }}">{{ $material->name }}</label>
                                @endforeach
                            </div>
                        </div>
                        <label class="f" for="fPrice">Preço de venda
                            <span class="money"><span>R$</span><input class="input" id="fPrice" inputmode="decimal" wire:model.live.debounce.300ms="price" placeholder="99,90"></span>
                            @isset($errorsBag['price'])<span class="err">{{ $errorsBag['price'] }}</span>@endisset
                        </label>
                        <label class="f" for="fOld">Preço antigo <small>Opcional · preencha para mostrar o desconto</small>
                            <span class="money"><span>R$</span><input class="input" id="fOld" inputmode="decimal" wire:model.live.debounce.300ms="oldPrice" placeholder="129,90"></span>
                        </label>
                        <label class="f full" for="fDesc">Descrição <small>Opcional · medidas, pedra, fecho</small>
                            <textarea class="textarea" id="fDesc" wire:model="description" placeholder="Corrente veneziana 45 cm..."></textarea>
                        </label>
                    </div>
                </section>
            </div>

            <div style="display:flex;flex-direction:column;gap:20px;min-width:0">
                <section class="card panel">
                    <h2>Vitrine</h2>
                    <label class="switch"><input type="checkbox" wire:model="isActive"><span class="track"></span><span style="color:var(--ink)">Mostrar no site</span></label>
                    <label class="switch"><input type="checkbox" wire:model="isBestSeller"><span class="track"></span><span style="color:var(--ink)">Mais vendidos</span></label>
                    <label class="switch"><input type="checkbox" wire:model.live="isNewRelease"><span class="track"></span><span style="color:var(--ink)">Lançamentos</span></label>
                    <p class="hint">Acabou a peça? Desligue "Mostrar no site".</p>
                </section>

                <section class="card pv">
                    <span class="pv-label">Prévia no site</span>
                    <div id="pv" style="display:flex;flex-direction:column;gap:6px">
                        <div class="pv-img">
                            @if ($preview->coverUrl)<img src="{{ $preview->coverUrl }}" alt="">@endif
                            @if ($preview->badge)<span class="pv-tag {{ $preview->badge === 'Novo' ? 'new' : '' }}">{{ $preview->badge }}</span>@endif
                        </div>
                        <span class="nm">{{ $preview->name }}</span>
                        <span class="pr">
                            @if ($preview->oldPrice)<s>{{ $preview->oldPrice->format() }}</s>@endif
                            <b>{{ $preview->price->format() }}</b>
                        </span>
                        <span class="inst">ou {{ $preview->installments }}x de {{ $preview->installmentAmount->format() }} sem juros</span>
                        <span class="pix">{{ $preview->pixPrice->format() }} no Pix</span>
                    </div>
                </section>
            </div>
        </div>

        <div class="form-bar" style="margin-top:20px">
            @if ($isEditing)
                <button class="btn btn-danger" type="button" wire:click="delete" wire:confirm="Excluir esta peça? Isso não pode ser desfeito."><x-icon name="trash" />Excluir</button>
            @else
                <span class="hint">Nada é salvo até você clicar em Salvar.</span>
            @endif
            <div class="right">
                <a class="btn btn-ghost" href="{{ route('admin.products') }}">Cancelar</a>
                <button class="btn btn-primary" type="submit">Salvar peça</button>
            </div>
        </div>
    </form>
</div>
