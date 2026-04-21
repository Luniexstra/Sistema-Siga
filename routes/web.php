<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SecurityController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/forgot-password', [SecurityController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [SecurityController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [SecurityController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [SecurityController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::view('/inicio', 'dashboard')->name('dashboard');
    Route::get('/seguridad', [SecurityController::class, 'showSecurity'])->name('pages.security');
    Route::put('/seguridad/contrasena', [SecurityController::class, 'updatePassword'])->name('security.password.update');
    Route::post('/email/verification-notification', [SecurityController::class, 'sendVerificationEmail'])->name('verification.send');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/email/verify/{id}/{hash}', [SecurityController::class, 'verifyEmail'])
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');

Route::middleware(['auth', 'role:' . User::ROLE_ADMINISTRADOR . ',' . User::ROLE_RECEPCIONISTA])->group(function () {
    Route::view('/alumnos', 'pages.alumnos')->name('pages.alumnos');
});

Route::middleware(['auth', 'role:' . implode(',', User::roles())])->group(function () {
    Route::view('/clases', 'pages.clases')->name('pages.clases');
    Route::view('/evaluaciones', 'pages.evaluaciones')->name('pages.evaluaciones');
    Route::view('/observaciones', 'pages.observaciones')->name('pages.observaciones');
});

Route::middleware(['auth', 'role:' . User::ROLE_ADMINISTRADOR . ',' . User::ROLE_RECEPCIONISTA . ',' . User::ROLE_ALUMNO])->group(function () {
    Route::view('/pagos', 'pages.pagos')->name('pages.pagos');
});

Route::middleware(['auth', 'role:' . User::ROLE_ADMINISTRADOR])->group(function () {
    Route::view('/usuarios', 'pages.users')->name('pages.users');
});
