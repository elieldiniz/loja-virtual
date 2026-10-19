<?php

use App\Livewire\Admin\Login;
use App\Models\User;
use Livewire\Livewire;

it('logs in with valid credentials', function () {
    User::factory()->create(['email' => 'loja@orvalha.test', 'password' => 'segredo123']);

    Livewire::test(Login::class)
        ->set('email', 'loja@orvalha.test')
        ->set('password', 'segredo123')
        ->call('login')
        ->assertRedirect(route('admin.dashboard'));

    expect(auth()->check())->toBeTrue();
});

it('shows the same generic error for a wrong password and an unknown email', function () {
    User::factory()->create(['email' => 'loja@orvalha.test', 'password' => 'segredo123']);

    $wrongPassword = Livewire::test(Login::class)
        ->set('email', 'loja@orvalha.test')->set('password', 'errada')->call('login')
        ->errors()->get('email');

    $unknown = Livewire::test(Login::class)
        ->set('email', 'ninguem@orvalha.test')->set('password', 'qualquer')->call('login')
        ->errors()->get('email');

    expect($wrongPassword)->toBe($unknown)->and($wrongPassword[0])->toBe(__('auth.failed'));
});

it('blocks the sixth failed attempt within a minute', function () {
    User::factory()->create(['email' => 'loja@orvalha.test', 'password' => 'segredo123']);

    foreach (range(1, 5) as $i) {
        Livewire::test(Login::class)->set('email', 'loja@orvalha.test')->set('password', 'errada')->call('login');
    }

    $component = Livewire::test(Login::class)
        ->set('email', 'loja@orvalha.test')->set('password', 'segredo123')->call('login');

    expect($component->errors()->get('email')[0])->toContain('Muitas tentativas')
        ->and(auth()->check())->toBeFalse();
});

it('requires authentication on every panel route', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'painel') && $route->uri() !== 'painel/entrar');

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        $method = in_array('GET', $route->methods()) ? 'get' : 'post';
        $this->{$method}('/'.$route->uri())->assertRedirect(route('login'));
    }
});

it('logs out, invalidates the session and redirects to the login', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('admin.logout'))->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

it('renders the panel shell for an authenticated user', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Painel')
        ->assertSee('Categorias')
        ->assertSee('Sair');
});

it('redirects logged in users away from the login screen', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('login'))->assertRedirect(route('admin.dashboard'));
});
