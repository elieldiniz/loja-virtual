<?php

use App\Livewire\Admin\Login;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

it('sends security headers on every web response', function () {
    $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('Referrer-Policy');
});

it('keeps the panel out of search engines', function () {
    $this->get(route('login'))->assertSee('noindex', false);
});

it('does not allow mass assigning guarded columns on users', function () {
    $user = new User(['name' => 'A', 'email' => 'a@a.test', 'password' => 'x', 'remember_token' => 'hack', 'email_verified_at' => now()]);

    expect($user->remember_token)->toBeNull()->and($user->email_verified_at)->toBeNull();
});

it('does not allow editing sales through mass assignment of sensitive ids', function () {
    $sale = Sale::factory()->create();

    expect(fn () => $sale->update(['customer_id' => Customer::factory()->create()->id]))->toThrow(LogicException::class);
});

it('has no model using an empty guarded list', function () {
    foreach (glob(app_path('Models/*.php')) as $file) {
        expect(file_get_contents($file))->not->toContain('$guarded = []');
    }
});

it('rate limits repeated failed logins from the same email and ip', function () {
    foreach (range(1, 5) as $i) {
        Livewire::test(Login::class)->set('email', 'x@x.test')->set('password', 'bad')->call('login');
    }

    expect(RateLimiter::tooManyAttempts('x@x.test|127.0.0.1', 5))->toBeTrue();
});
