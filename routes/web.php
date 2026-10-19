<?php

use App\Http\Controllers\Admin\LogoutController;
use App\Livewire\Admin\Categories;
use App\Livewire\Admin\ComingSoon;
use App\Livewire\Admin\Customers\Form as CustomerForm;
use App\Livewire\Admin\Customers\Index as CustomerIndex;
use App\Livewire\Admin\Login;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Products\Index as ProductIndex;
use App\Livewire\Admin\Sales\Create as SaleCreate;
use App\Livewire\Admin\StoreSettings;
use Illuminate\Support\Facades\Route;

Route::view('/', 'storefront.placeholder')->name('home');

Route::prefix('painel')->group(function () {
    Route::get('entrar', Login::class)->middleware('guest')->name('login');

    Route::middleware('auth')->group(function () {
        Route::post('sair', LogoutController::class)->name('admin.logout');

        Route::get('/', ComingSoon::class)->defaults('heading', 'Painel')->defaults('current', 'dash')->name('admin.dashboard');
        Route::get('vendas', ComingSoon::class)->defaults('heading', 'Vendas')->defaults('current', 'sales')->name('admin.sales');
        Route::get('vendas/nova', SaleCreate::class)->name('admin.sales.create');
        Route::get('vendas/{sale}', ComingSoon::class)->defaults('heading', 'Venda')->defaults('current', 'sales')->name('admin.sales.show');
        Route::get('clientes', CustomerIndex::class)->name('admin.customers');
        Route::get('clientes/nova', CustomerForm::class)->name('admin.customers.create');
        Route::get('clientes/{customer}', CustomerForm::class)->name('admin.customers.edit');
        Route::get('produtos', ProductIndex::class)->name('admin.products');
        Route::get('produtos/nova', ProductForm::class)->name('admin.products.create');
        Route::get('produtos/{product}/editar', ProductForm::class)->name('admin.products.edit');
        Route::get('categorias', Categories::class)->name('admin.categories');
        Route::get('loja', StoreSettings::class)->name('admin.store');
    });
});
