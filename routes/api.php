<?php

use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\ClaseController;
use App\Http\Controllers\EvaluacionController;
use App\Http\Controllers\ObservacionController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

$allRoles = implode(',', User::roles());

Route::middleware('role:' . $allRoles)->get('me', function () {
    return request()->user()->load('alumno');
});

Route::middleware('role:' . User::ROLE_ADMINISTRADOR . ',' . User::ROLE_RECEPCIONISTA)
    ->group(function () {
        Route::get('alumnos', [AlumnoController::class, 'index'])->name('alumnos.index');
        Route::get('catalogos/instructores', function () {
            return User::query()
                ->where('role', User::ROLE_INSTRUCTOR)
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        });
        Route::post('alumnos', [AlumnoController::class, 'store'])->name('alumnos.store');
        Route::delete('alumnos/{alumno}', [AlumnoController::class, 'destroy'])->name('alumnos.destroy');
        Route::post('pagos', [PagoController::class, 'store'])->name('pagos.store');
        Route::match(['put', 'patch'], 'pagos/{pago}', [PagoController::class, 'update'])->name('pagos.update');
        Route::delete('pagos/{pago}', [PagoController::class, 'destroy'])->name('pagos.destroy');
    });

Route::middleware('role:' . User::ROLE_ADMINISTRADOR . ',' . User::ROLE_RECEPCIONISTA . ',' . User::ROLE_ALUMNO)
    ->group(function () {
        Route::get('alumnos/{alumno}', [AlumnoController::class, 'show'])->name('alumnos.show');
        Route::match(['put', 'patch'], 'alumnos/{alumno}', [AlumnoController::class, 'update'])->name('alumnos.update');
        Route::get('alumnos/{alumno}/estado-cuenta', [PagoController::class, 'estadoCuenta']);
        Route::get('alumnos/{alumno}/estado-cuenta/pdf', [PagoController::class, 'estadoCuentaPdf']);
        Route::get('pagos', [PagoController::class, 'index'])->name('pagos.index');
        Route::get('pagos/{pago}', [PagoController::class, 'show'])->name('pagos.show');
    });

Route::middleware('role:' . User::ROLE_ADMINISTRADOR . ',' . User::ROLE_RECEPCIONISTA . ',' . User::ROLE_INSTRUCTOR . ',' . User::ROLE_ALUMNO)
    ->group(function () {
        Route::get('clases', [ClaseController::class, 'index'])->name('clases.index');
        Route::get('clases/{clase}', [ClaseController::class, 'show'])->name('clases.show');
        Route::get('evaluaciones', [EvaluacionController::class, 'index'])->name('evaluaciones.index');
        Route::get('observaciones', [ObservacionController::class, 'index'])->name('observaciones.index');
        Route::get('observaciones/{observacione}', [ObservacionController::class, 'show'])->name('observaciones.show');
        Route::get('evaluaciones/{evaluacion}', [EvaluacionController::class, 'show'])->name('evaluaciones.show');
    });

Route::middleware('role:' . User::ROLE_ADMINISTRADOR . ',' . User::ROLE_RECEPCIONISTA)
    ->group(function () {
        Route::post('clases', [ClaseController::class, 'store'])->name('clases.store');
        Route::match(['put', 'patch'], 'clases/{clase}', [ClaseController::class, 'update'])->name('clases.update');
        Route::delete('clases/{clase}', [ClaseController::class, 'destroy'])->name('clases.destroy');
    });

Route::middleware('role:' . User::ROLE_INSTRUCTOR)
    ->group(function () {
        Route::post('evaluaciones', [EvaluacionController::class, 'store'])->name('evaluaciones.store');
        Route::match(['put', 'patch'], 'evaluaciones/{evaluacion}', [EvaluacionController::class, 'update'])->name('evaluaciones.update');
        Route::delete('evaluaciones/{evaluacion}', [EvaluacionController::class, 'destroy'])->name('evaluaciones.destroy');

        Route::post('observaciones', [ObservacionController::class, 'store'])->name('observaciones.store');
        Route::match(['put', 'patch'], 'observaciones/{observacione}', [ObservacionController::class, 'update'])->name('observaciones.update');
        Route::delete('observaciones/{observacione}', [ObservacionController::class, 'destroy'])->name('observaciones.destroy');
    });

Route::middleware('role:' . User::ROLE_ADMINISTRADOR)
    ->group(function () {
        Route::apiResource('users', UserController::class);
    });
