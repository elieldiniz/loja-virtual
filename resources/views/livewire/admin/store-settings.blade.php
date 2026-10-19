<div>
    <div class="page-head"><div><h1>Loja</h1><p>Aparece no topo, no rodapé, nos preços e em todos os botões de WhatsApp.</p></div></div>

    <form wire:submit="save" style="display:flex;flex-direction:column;gap:20px">
        <div class="form-grid">
            <section class="card panel">
                <h2>Contato</h2>
                <div class="fields">
                    <label class="f" for="sWa">WhatsApp<input class="input" id="sWa" wire:model="whatsapp" inputmode="tel" placeholder="(69) 99999-0000">@error('whatsapp')<span class="err">{{ $message }}</span>@enderror</label>
                    <label class="f" for="sIg">Instagram<input class="input" id="sIg" wire:model="instagram" placeholder="@styliprates"></label>
                    <label class="f full" for="sStreet">Endereço<input class="input" id="sStreet" wire:model="address"></label>
                    <label class="f" for="sCity">Cidade<input class="input" id="sCity" wire:model="city"></label>
                    <label class="f" for="sState">Estado<input class="input" id="sState" wire:model="state"></label>
                    <label class="f full" for="sCnpj">CNPJ <small>Aparece no rodapé</small><input class="input" id="sCnpj" wire:model="cnpj"></label>
                </div>
            </section>
            <section class="card panel">
                <h2>Horários</h2>
                <div class="hours">
                    <span>Seg a sex</span><input class="input" wire:model="hoursWeekdays" aria-label="Horário de segunda a sexta" placeholder="9h às 18h">
                    <span>Sábado</span><input class="input" wire:model="hoursSaturday" aria-label="Horário de sábado" placeholder="9h às 13h">
                    <span>Domingo</span><input class="input" wire:model="hoursSunday" aria-label="Horário de domingo" placeholder="Fechado">
                </div>
            </section>
        </div>

        <div class="form-grid">
            <section class="card panel">
                <h2>Pagamento e garantia</h2>
                <div class="fields">
                    <label class="f" for="sPix">Desconto no Pix (%)<input class="input" id="sPix" wire:model="pixDiscountPercent" inputmode="numeric">@error('pixDiscountPercent')<span class="err">{{ $message }}</span>@enderror</label>
                    <label class="f" for="sInst">Parcelas sem juros<input class="input" id="sInst" wire:model="maxInstallments" inputmode="numeric">@error('maxInstallments')<span class="err">{{ $message }}</span>@enderror</label>
                    <label class="f full" for="sWar">Garantia do banho<input class="input" id="sWar" wire:model="platingWarranty" placeholder="Ex.: 6 meses"></label>
                </div>
                <p class="hint">Os preços no Pix e o parcelamento dos cards são calculados com esses números.</p>
            </section>
            <section class="card panel">
                <h2>Faixa de promoções</h2>
                <p class="hint">Frases que alternam no topo do site.</p>
                <div class="promo-list">
                    @foreach ($promoPhrases as $i => $phrase)
                        <div class="promo-item" wire:key="promo-{{ $i }}">
                            <input class="input" wire:model="promoPhrases.{{ $i }}" aria-label="Frase {{ $i + 1 }}">
                            <button class="icon-btn" type="button" wire:click="removePromo({{ $i }})" aria-label="Remover frase"><x-icon name="x" /></button>
                        </div>
                    @endforeach
                </div>
                <button class="btn btn-ghost" type="button" wire:click="addPromo" style="align-self:flex-start"><x-icon name="plus" />Adicionar frase</button>
            </section>
        </div>

        <section class="card panel">
            <h2>Atendimento no site</h2>
            <p class="hint">Deixa a vitrine com cara de conversa, sem nome de atendente: fala sempre como "a gente".</p>
            <div class="care-grid">
                <div class="fields">
                    <label class="f full" for="cWelcome">Frase de boas-vindas <small>Aparece no balão e no topo do atendimento</small><input class="input" id="cWelcome" wire:model="welcomeMessage" placeholder="Oi! Quer ajuda pra escolher? Fala com a gente 😊"></label>
                    <label class="f full" for="cGift">Presente de aniversário <small>% de desconto na mensagem</small><input class="input" id="cGift" wire:model="birthdayGiftPercent" inputmode="numeric">@error('birthdayGiftPercent')<span class="err">{{ $message }}</span>@enderror</label>
                </div>
                <div class="fab-box">
                    <span class="flabel">Botão de atendimento</span>
                    <div class="fab-row" id="fabPv">
                        <span class="fab-pv">@if ($buttonMode === 'photo' && $photoUrl)<img src="{{ $photoUrl }}" alt="">@else<x-monogram variant="light" />@endif<span class="wa-mini"></span></span>
                        <div class="chips-choose">
                            <label class="chip-opt"><input type="radio" wire:model.live="buttonMode" value="logo">Logo da STyli Prates</label>
                            <label class="chip-opt"><input type="radio" wire:model.live="buttonMode" value="photo">Foto da loja</label>
                        </div>
                    </div>
                    @if ($buttonMode === 'photo')
                        <label class="add-photo slim" for="cFabIn"><x-icon name="camera" />{{ $photoUrl ? 'Trocar foto' : 'Enviar foto da loja, da vitrine ou das mãos embalando' }}<input id="cFabIn" type="file" accept="image/*" wire:model="buttonPhoto"></label>
                        @if ($photoUrl)<button class="link-btn" type="button" wire:click="removePhoto" style="align-self:flex-start">Remover foto</button>@endif
                        @error('buttonPhoto')<span class="err">{{ $message }}</span>@enderror
                    @endif
                </div>
            </div>

            <div class="fgroup">
                <span class="flabel">Atalhos de conversa <small>Cada um abre o WhatsApp com a pergunta pronta</small></span>
                <div class="promo-list">
                    @foreach ($shortcuts as $i => $shortcut)
                        <div class="promo-item" wire:key="short-{{ $i }}" style="flex-wrap:wrap">
                            <input class="input" wire:model="shortcuts.{{ $i }}.label" aria-label="Atalho {{ $i + 1 }}" placeholder="Texto do atalho" style="flex:1 1 220px">
                            <input class="input" wire:model="shortcuts.{{ $i }}.message" aria-label="Mensagem do atalho {{ $i + 1 }}" placeholder="Mensagem no WhatsApp (opcional)" style="flex:2 1 260px" @disabled($shortcut['gift'])>
                            <label class="chip-opt"><input type="checkbox" wire:model.live="shortcuts.{{ $i }}.gift">Abre ajuda para presente</label>
                            <button class="icon-btn" type="button" wire:click="removeShortcut({{ $i }})" aria-label="Remover atalho"><x-icon name="x" /></button>
                        </div>
                    @endforeach
                </div>
                <button class="btn btn-ghost" type="button" wire:click="addShortcut" style="align-self:flex-start"><x-icon name="plus" />Adicionar atalho</button>
            </div>

            <div class="invite-row">
                <label class="switch"><input type="checkbox" wire:model="helpInviteEnabled"><span class="track"></span><span style="color:var(--ink)">Convite de ajuda</span></label>
                <span class="hint">Abre depois de</span>
                <select class="select" wire:model="helpInviteMinutes" aria-label="Minutos" style="width:auto">
                    @foreach ([1, 2, 3, 5] as $m)<option value="{{ $m }}">{{ $m }} {{ $m === 1 ? 'minuto' : 'minutos' }}</option>@endforeach
                </select>
                <span class="hint">no site sem nada na sacola. Aparece uma vez por visita.</span>
                @error('helpInviteMinutes')<span class="err">{{ $message }}</span>@enderror
            </div>
        </section>

        <div class="form-bar">
            <span class="hint">As mudanças vão para o site assim que você salvar.</span>
            <div class="right"><button class="btn btn-primary" type="submit">Salvar</button></div>
        </div>
    </form>
</div>
