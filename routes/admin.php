<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('parametres', [SettingsController::class, 'edit'])->name('settings.edit');
Route::put('parametres/general', [SettingsController::class, 'updateGeneral'])->name('settings.general');
Route::put('parametres/paiement', [SettingsController::class, 'updatePayment'])->name('settings.payment');
Route::put('parametres/livraison', [SettingsController::class, 'updateShipping'])->name('settings.shipping');

Route::resource('categories', CategoryController::class)->except('show');
Route::resource('marques', BrandController::class)->except('show')->parameters(['marques' => 'brand'])->names('brands');

Route::post('produits/actions-groupees', [ProductController::class, 'bulk'])->name('products.bulk');
Route::resource('produits', ProductController::class)->except('show')->parameters(['produits' => 'product'])->names('products');
Route::post('produits/{product}/images', [ProductImageController::class, 'store'])->name('products.images.store');
Route::put('produits/{product}/images/ordre', [ProductImageController::class, 'reorder'])->name('products.images.reorder');
Route::delete('produits/{product}/images/{media}', [ProductImageController::class, 'destroy'])->name('products.images.destroy');
