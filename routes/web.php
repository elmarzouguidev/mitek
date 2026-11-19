<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

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
