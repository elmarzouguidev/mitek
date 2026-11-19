<?php

use App\Enums\PageKey;
use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\DashboardController as AccountDashboardController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\QuoteController as AccountQuoteController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Storefront\CallbackController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ContactController;
use App\Http\Controllers\Storefront\CreditNoteController;
use App\Http\Controllers\Storefront\FaqController;
use App\Http\Controllers\Storefront\InvoiceController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\QuoteController;
use App\Http\Controllers\Storefront\RobotsController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Controllers\Storefront\ServiceController;
use App\Http\Controllers\Storefront\SitemapController;
use App\Http\Middleware\EnsureOrderAccess;
use App\Http\Middleware\EnsureQuoteAccess;
use Illuminate\Support\Facades\Route;
use Spatie\Honeypot\ProtectAgainstSpam;

Route::get('/', HomeController::class)->name('home');

Route::get('services', [ServiceController::class, 'index'])->name('services.index');
Route::post('services/demande', [ServiceController::class, 'request'])->middleware([ProtectAgainstSpam::class, 'throttle:10,1'])->name('services.request');
Route::get('services/{slug}', [ServiceController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('services.show');

foreach (PageKey::cases() as $pageKey) {
    Route::get($pageKey->slug(), [PageController::class, 'show'])->defaults('key', $pageKey->value)->name('pages.'.$pageKey->value);
}

Route::get('faq', [FaqController::class, 'index'])->name('faq');
Route::get('rappel', [CallbackController::class, 'create'])->name('callback.create');
Route::post('rappel', [CallbackController::class, 'store'])->middleware([ProtectAgainstSpam::class, 'throttle:5,1'])->name('callback.store');
Route::get('contact', [ContactController::class, 'show'])->name('contact');
Route::post('contact', [ContactController::class, 'store'])->middleware([ProtectAgainstSpam::class, 'throttle:10,1'])->name('contact.store');

Route::get('robots.txt', RobotsController::class)->name('robots');
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('categories/{slug}', [CategoryController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('categories.show');
Route::get('produit/{slug}', [ProductController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('products.show');
Route::get('produit/{slug}/prix', [ProductController::class, 'price'])->where('slug', '[a-z0-9-]+')->middleware('throttle:120,1')->name('products.price');
Route::get('recherche', [SearchController::class, 'index'])->name('search');
Route::get('recherche/suggestions', [SearchController::class, 'suggestions'])->middleware('throttle:60,1')->name('search.suggestions');

Route::get('panier', [CartController::class, 'show'])->name('cart.show');
Route::get('panier/resume', [CartController::class, 'summary'])->name('cart.summary');
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
    Route::get('commande/{order:uuid}/avoir', [CreditNoteController::class, 'show'])->name('orders.credit-note');
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

Route::middleware('auth')->group(function (): void {
    Route::get('email/verification', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('email/verification/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('email/verification/envoyer', [EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');
});
