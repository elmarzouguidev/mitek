<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('parametres', [SettingsController::class, 'edit'])->name('settings.edit');
Route::put('parametres/general', [SettingsController::class, 'updateGeneral'])->name('settings.general');
Route::put('parametres/paiement', [SettingsController::class, 'updatePayment'])->name('settings.payment');
Route::put('parametres/livraison', [SettingsController::class, 'updateShipping'])->name('settings.shipping');
