<?php

use App\Http\Controllers\Admin\LogoutController;
use App\Livewire\Admin\ComingSoon;
use App\Livewire\Admin\Login;
use Illuminate\Support\Facades\Route;

Route::view('/', 'storefront.placeholder')->name('home');

Route::prefix('painel')->group(function () {
    Route::get('entrar', Login::class)->middleware('guest')->name('login');

    Route::middleware('auth')->group(function () {
        Route::post('sair', LogoutController::class)->name('admin.logout');

        Route::get('/', ComingSoon::class)->defaults('heading', 'Painel')->defaults('current', 'dash')->name('admin.dashboard');
        Route::get('vendas', ComingSoon::class)->defaults('heading', 'Vendas')->defaults('current', 'sales')->name('admin.sales');
        Route::get('clientes', ComingSoon::class)->defaults('heading', 'Clientes')->defaults('current', 'clients')->name('admin.customers');
        Route::get('produtos', ComingSoon::class)->defaults('heading', 'Produtos')->defaults('current', 'items')->name('admin.products');
        Route::get('categorias', ComingSoon::class)->defaults('heading', 'Categorias')->defaults('current', 'cats')->name('admin.categories');
        Route::get('loja', ComingSoon::class)->defaults('heading', 'Loja')->defaults('current', 'store')->name('admin.store');
    });
});
