<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderInvoiceController;
use App\Http\Controllers\Admin\OrderPaymentController;
use App\Http\Controllers\Admin\OrderStatusController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\QuoteRequestController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShippingZoneController;
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

Route::get('commandes', [OrderController::class, 'index'])->name('orders.index');
Route::get('commandes/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::get('commandes/{order}/facture', [OrderInvoiceController::class, 'show'])->name('orders.invoice');
Route::put('commandes/{order}/statut', [OrderStatusController::class, 'update'])->name('orders.status');
Route::post('commandes/{order}/justificatifs', [OrderPaymentController::class, 'storeProof'])->name('orders.proofs.store');
Route::get('paiements/{payment}/justificatifs/{media}', [OrderPaymentController::class, 'showProof'])->name('orders.proofs.show');
Route::post('paiements/{payment}/valider', [OrderPaymentController::class, 'validatePayment'])->name('orders.payments.validate');
Route::post('paiements/{payment}/refuser', [OrderPaymentController::class, 'rejectPayment'])->name('orders.payments.reject');

Route::resource('livraison', ShippingZoneController::class)->except('show')->parameters(['livraison' => 'zone'])->names('shipping');

Route::get('devis', [QuoteRequestController::class, 'index'])->name('quotes.index');
Route::get('devis/{quote}', [QuoteRequestController::class, 'show'])->name('quotes.show');
Route::post('devis/{quote}/reponse', [QuoteRequestController::class, 'reply'])->name('quotes.reply');
Route::put('devis/{quote}/cloture', [QuoteRequestController::class, 'close'])->name('quotes.close');
