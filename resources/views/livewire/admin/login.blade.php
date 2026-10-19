<div class="login">
    <form class="login-card" wire:submit="login">
        <div class="brand-mark"><b>ORVALHA</b><span>Semijoias</span></div>
        <p>Entre para gerenciar as peças e a loja.</p>

        <label class="f" for="lEmail">E-mail
            <input class="input" id="lEmail" type="email" wire:model="email" autocomplete="username" autofocus>
        </label>
        <label class="f" for="lPass">Senha
            <input class="input" id="lPass" type="password" wire:model="password" autocomplete="current-password">
        </label>

        @error('email')<span class="err" role="alert">{{ $message }}</span>@enderror
        @error('password')<span class="err" role="alert">{{ $message }}</span>@enderror

        <button class="btn btn-primary" type="submit">Entrar</button>
    </form>
</div>
