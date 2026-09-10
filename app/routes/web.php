<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\IncidenciaController;
use App\Http\Controllers\OperadorIncidenciaController;
use App\Http\Controllers\PanelController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas web — Gestión de Incidencias (MVP)
|--------------------------------------------------------------------------
| Etapa MVP: el login NO valida contraseña. Sólo se pide el nombre de
| usuario; si existe en `usuarios`, se inicia sesión y se bifurca según
| el rol (Socio -> Panel de Socio; resto -> panel general).
*/

Route::get('/', fn () => redirect()->route('login'));

// Vista Login
Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Redirección posterior al login según rol
Route::get('/ingresar', [AuthController::class, 'destino'])
    ->middleware('usuario.auth')
    ->name('post-login.destino');

// Paneles y circuito de incidencias (requieren usuario en sesión)
Route::middleware('usuario.auth')->group(function () {
    Route::get('/panel-socio', [PanelController::class, 'socio'])->name('panel.socio');
    Route::get('/panel', [PanelController::class, 'general'])->name('panel.general');

    // CRUD de incidencias (rol Socio)
    Route::get('/incidencias', [IncidenciaController::class, 'index'])->name('incidencias.index');
    Route::get('/incidencias/nueva', [IncidenciaController::class, 'create'])->name('incidencias.create');
    Route::post('/incidencias', [IncidenciaController::class, 'store'])->name('incidencias.store');
    Route::get('/incidencias/{incidencia}/editar', [IncidenciaController::class, 'edit'])->name('incidencias.edit');
    Route::patch('/incidencias/{incidencia}', [IncidenciaController::class, 'update'])->name('incidencias.update');
    Route::get('/incidencias/{incidencia}/confirmar-alta', [IncidenciaController::class, 'confirmarAlta'])->name('incidencias.confirmar-alta');
    Route::get('/incidencias/{incidencia}', [IncidenciaController::class, 'show'])->name('incidencias.show');
    Route::get('/incidencias/{incidencia}/historial', [IncidenciaController::class, 'historial'])->name('incidencias.historial');
    Route::post('/incidencias/{incidencia}/confirmar', [IncidenciaController::class, 'confirmar'])->name('incidencias.confirmar');
    Route::post('/incidencias/{incidencia}/cancelar', [IncidenciaController::class, 'cancelar'])->name('incidencias.cancelar');

    // Panel del operador: ve TODAS las incidencias y gestiona criticidad / responsables
    Route::get('/operador/incidencias', [OperadorIncidenciaController::class, 'index'])->name('operador.incidencias.index');
    Route::get('/operador/incidencias/{incidencia}', [OperadorIncidenciaController::class, 'edit'])->name('operador.incidencias.edit');
    Route::patch('/operador/incidencias/{incidencia}', [OperadorIncidenciaController::class, 'update'])->name('operador.incidencias.update');
    Route::post('/operador/incidencias/{incidencia}/cancelar', [OperadorIncidenciaController::class, 'cancelar'])->name('operador.incidencias.cancelar');
    Route::post('/operador/incidencias/{incidencia}/resolver', [OperadorIncidenciaController::class, 'resolver'])->name('operador.incidencias.resolver');
});
