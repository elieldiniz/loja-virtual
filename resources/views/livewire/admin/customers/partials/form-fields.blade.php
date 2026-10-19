<section class="card panel">
    <h2>Dados</h2>
    <form wire:submit="save">
        <div class="fields">
            <label class="f full" for="cName">Nome<input class="input" id="cName" wire:model="name">@error('name')<span class="err">{{ $message }}</span>@enderror</label>
            <label class="f" for="cPhone">WhatsApp<input class="input" id="cPhone" wire:model="whatsapp" inputmode="tel" placeholder="(69) 9____-____">@error('whatsapp')<span class="err">{{ $message }}</span>@enderror</label>
            <label class="f" for="cCity">Cidade<input class="input" id="cCity" wire:model="city">@error('city')<span class="err">{{ $message }}</span>@enderror</label>
            <label class="f" for="cBday">Aniversário <small>dia/mês</small><input class="input" id="cBday" wire:model="birthday" placeholder="21/06">@error('birthday')<span class="err">{{ $message }}</span>@enderror</label>
            <label class="f full" for="cNotes">Observações<textarea class="textarea" id="cNotes" wire:model="notes"></textarea></label>
        </div>
        <div class="cart-acts" style="margin-top:16px">
            @if ($isEditing)<button class="btn btn-danger" type="button" wire:click="askDelete"><x-icon name="trash" />Excluir</button>@endif
            <button class="btn btn-primary" type="submit">Salvar</button>
        </div>
    </form>
</section>
