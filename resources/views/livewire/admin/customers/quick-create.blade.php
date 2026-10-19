<div class="sheet-bg" wire:click.self="close">
    <form class="sheet wide" wire:submit="save" role="dialog" aria-modal="true" aria-labelledby="qfH">
        <div class="sheet-head">
            <h3 id="qfH">Nova cliente</h3>
            <button class="icon-btn" type="button" wire:click="close" aria-label="Fechar"><x-icon name="x" /></button>
        </div>
        <p>Ela já fica selecionada nesta venda.</p>
        <div class="fields">
            <label class="f full" for="mName">Nome<input class="input" id="mName" wire:model="name" autocomplete="off" autofocus>@error('name')<span class="err">{{ $message }}</span>@enderror</label>
            <label class="f" for="mPhone">WhatsApp<input class="input" id="mPhone" wire:model="whatsapp" inputmode="tel" placeholder="(69) 9____-____">@error('whatsapp')<span class="err">{{ $message }}</span>@enderror</label>
            <label class="f" for="mCity">Cidade<input class="input" id="mCity" wire:model="city"></label>
            <label class="f" for="mBday">Aniversário <small>dia/mês</small><input class="input" id="mBday" wire:model="birthday" placeholder="21/06">@error('birthday')<span class="err">{{ $message }}</span>@enderror</label>
            <label class="f full" for="mNotes">Observações<textarea class="textarea" id="mNotes" wire:model="notes" style="min-height:70px"></textarea></label>
        </div>
        <div class="acts">
            <button class="btn btn-ghost" type="button" wire:click="close">Cancelar</button>
            <button class="btn btn-primary" type="submit"><x-icon name="check" />Cadastrar e selecionar</button>
        </div>
    </form>
</div>
