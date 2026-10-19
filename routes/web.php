<?php

use App\Http\Controllers\Admin\LogoutController;
use App\Livewire\Admin\Categories;
use App\Livewire\Admin\ComingSoon;
use App\Livewire\Admin\Login;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Products\Index as ProductIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'storefront.placeholder')->name('home');

Route::prefix('painel')->group(function () {
    Route::get('entrar', Login::class)->middleware('guest')->name('login');

    Route::middleware('auth')->group(function () {
        Route::post('sair', LogoutController::class)->name('admin.logout');

        Route::get('/', ComingSoon::class)->defaults('heading', 'Painel')->defaults('current', 'dash')->name('admin.dashboard');
        Route::get('vendas', ComingSoon::class)->defaults('heading', 'Vendas')->defaults('current', 'sales')->name('admin.sales');
        Route::get('clientes', ComingSoon::class)->defaults('heading', 'Clientes')->defaults('current', 'clients')->name('admin.customers');
        Route::get('produtos', ProductIndex::class)->name('admin.products');
        Route::get('produtos/nova', ProductForm::class)->name('admin.products.create');
        Route::get('produtos/{product}/editar', ProductForm::class)->name('admin.products.edit');
        Route::get('categorias', Categories::class)->name('admin.categories');
        Route::get('loja', ComingSoon::class)->defaults('heading', 'Loja')->defaults('current', 'store')->name('admin.store');
    });
});
