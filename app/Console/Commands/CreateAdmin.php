<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('admin:create {--name= : Nome da administradora} {--email= : E-mail de acesso} {--password= : Senha}')]
#[Description('Cria ou atualiza a administradora do painel (usa ADMIN_NAME, ADMIN_EMAIL e ADMIN_PASSWORD como padrão)')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $name = $this->option('name') ?: env('ADMIN_NAME') ?: 'Administradora';
        $email = $this->option('email') ?: env('ADMIN_EMAIL') ?: $this->ask('E-mail de acesso');
        $password = $this->option('password') ?: env('ADMIN_PASSWORD') ?: $this->secret('Senha');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $password) {
            $this->error('Informe um e-mail válido e uma senha.');

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password)],
        );

        $this->info("Administradora {$user->email} ".($user->wasRecentlyCreated ? 'criada' : 'atualizada').'.');

        return self::SUCCESS;
    }
}
