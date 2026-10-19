<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates the administrator with a hashed password', function () {
    $this->artisan('admin:create', ['--email' => 'loja@orvalha.test', '--password' => 'segredo123', '--name' => 'Loja'])
        ->assertSuccessful();

    $user = User::where('email', 'loja@orvalha.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->password)->not->toBe('segredo123')
        ->and(Hash::check('segredo123', $user->password))->toBeTrue();
});

it('is idempotent and updates the password on re-run', function () {
    $this->artisan('admin:create', ['--email' => 'loja@orvalha.test', '--password' => 'primeira1']);
    $this->artisan('admin:create', ['--email' => 'loja@orvalha.test', '--password' => 'segunda22']);

    expect(User::where('email', 'loja@orvalha.test')->count())->toBe(1)
        ->and(Hash::check('segunda22', User::first()->password))->toBeTrue();
});

it('rejects an invalid email', function () {
    $this->artisan('admin:create', ['--email' => 'invalido', '--password' => 'segredo123'])->assertFailed();

    expect(User::count())->toBe(0);
});
