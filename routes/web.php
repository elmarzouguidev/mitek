<?php

use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\DashboardController as AccountDashboardController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\QuoteController as AccountQuoteController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\InvoiceController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\QuoteController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Middleware\EnsureOrderAccess;
use App\Http\Middleware\EnsureQuoteAccess;
use Illuminate\Support\Facades\Route;
use Spatie\Honeypot\ProtectAgainstSpam;

Route::get('/', HomeController::class)->name('home');

Route::get('categories/{slug}', [CategoryController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('categories.show');
Route::get('produit/{slug}', [ProductController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('products.show');
Route::get('produit/{slug}/prix', [ProductController::class, 'price'])->where('slug', '[a-z0-9-]+')->middleware('throttle:120,1')->name('products.price');
Route::get('recherche', [SearchController::class, 'index'])->name('search');
Route::get('recherche/suggestions', [SearchController::class, 'suggestions'])->middleware('throttle:60,1')->name('search.suggestions');

Route::get('panier', [CartController::class, 'show'])->name('cart.show');
Route::post('panier', [CartController::class, 'store'])->middleware('throttle:60,1')->name('cart.store');
Route::patch('panier/{slug}', [CartController::class, 'update'])->where('slug', '[a-z0-9-]+')->name('cart.update');
Route::delete('panier/{slug}', [CartController::class, 'destroy'])->where('slug', '[a-z0-9-]+')->name('cart.destroy');

Route::get('commande', [CheckoutController::class, 'show'])->name('checkout.show');
Route::get('commande/estimation', [CheckoutController::class, 'estimate'])->middleware('throttle:60,1')->name('checkout.estimate');
Route::post('commande', [CheckoutController::class, 'store'])->middleware([ProtectAgainstSpam::class, 'throttle:10,1'])->name('checkout.store');

Route::get('devis', [QuoteController::class, 'create'])->name('quotes.create');
Route::post('devis', [QuoteController::class, 'store'])->middleware([ProtectAgainstSpam::class, 'throttle:10,1'])->name('quotes.store');
Route::get('devis/{quote:uuid}', [QuoteController::class, 'show'])->middleware(EnsureQuoteAccess::class)->name('quotes.show');

Route::middleware(EnsureOrderAccess::class)->group(function (): void {
    Route::get('commande/{order:uuid}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('commande/{order:uuid}/facture', [InvoiceController::class, 'show'])->name('orders.invoice');
    Route::post('commande/{order:uuid}/justificatif', [OrderController::class, 'storeProof'])->middleware('throttle:20,1')->name('orders.proof.store');
});

Route::middleware('guest')->group(function (): void {
    Route::get('connexion', [LoginController::class, 'create'])->name('login');
    Route::post('connexion', [LoginController::class, 'store'])->middleware('throttle:20,1');
    Route::get('inscription', [RegisterController::class, 'create'])->name('register');
    Route::post('inscription', [RegisterController::class, 'store'])->middleware('throttle:10,1');
    Route::get('mot-de-passe-oublie', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('mot-de-passe-oublie', [ForgotPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
    Route::get('reinitialiser-mot-de-passe/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('reinitialiser-mot-de-passe', [ResetPasswordController::class, 'store'])->name('password.update');
});

Route::post('deconnexion', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('compte')->name('account.')->group(function (): void {
    Route::get('/', AccountDashboardController::class)->name('index');
    Route::get('commandes', [AccountOrderController::class, 'index'])->name('orders');
    Route::get('devis', [AccountQuoteController::class, 'index'])->name('quotes');

    Route::get('adresses', [AddressController::class, 'index'])->name('addresses');
    Route::post('adresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::get('adresses/{address}/modifier', [AddressController::class, 'edit'])->name('addresses.edit');
    Route::put('adresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
    Route::delete('adresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');

    Route::get('informations', [ProfileController::class, 'edit'])->name('profile');
    Route::put('informations', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('mot-de-passe', [ProfileController::class, 'updatePassword'])->name('password.update');
});
