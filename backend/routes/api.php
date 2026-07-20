<?php

use App\Http\Controllers\AccesoQrController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\ExternalTicketController;
use App\Http\Controllers\FotocheckController;
use App\Http\Controllers\ImageProxyController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\OficinaController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\PublicFotocheckController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\TipoSolicitudController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

Route::get('/public/fotocheck/{codigo}', [PublicFotocheckController::class, 'show'])
    ->middleware('throttle:30,1');

Route::get('/proxy/image/{url}', [ImageProxyController::class, 'show'])
    ->middleware('throttle:30,1');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard_ver');

    // Trabajadores
    Route::apiResource('trabajadores', TrabajadorController::class)
        ->middleware('permission:trabajadores_ver');
    Route::post('trabajadores/importar', [TrabajadorController::class, 'importar'])
        ->middleware('permission:trabajadores_crear');
    Route::get('trabajadores/{id}/fotochecks', [FotocheckController::class, 'porTrabajador'])
        ->middleware('permission:fotochecks_ver');

    // Estudiantes
    Route::apiResource('estudiantes', EstudianteController::class)
        ->middleware('permission:estudiantes_ver');
    Route::post('estudiantes/importar', [EstudianteController::class, 'importar'])
        ->middleware('permission:estudiantes_crear');
    Route::get('plantilla-estudiantes', [EstudianteController::class, 'plantilla'])
        ->middleware('permission:estudiantes_ver');

    // Fotochecks
    Route::apiResource('fotochecks', FotocheckController::class)->only(['index', 'store', 'show', 'destroy'])
        ->middleware('permission:fotochecks_ver');
    Route::post('fotochecks/generar', [FotocheckController::class, 'generar'])
        ->middleware('permission:fotochecks_generar');

    // Usuarios
    Route::apiResource('usuarios', UsuarioController::class)
        ->middleware('permission:usuarios_ver');
    Route::post('usuarios/{id}/desbloquear', [UsuarioController::class, 'desbloquear'])
        ->middleware('permission:usuarios_editar');

    // Roles
    Route::apiResource('roles', RolController::class)
        ->middleware('permission:roles_ver');

    // Permisos
    Route::apiResource('permisos', PermisoController::class)
        ->middleware('permission:permisos_ver');

    // Logs
    Route::get('/logs', [LogController::class, 'index'])
        ->middleware('permission:logs_ver');

    // Accesos QR
    Route::get('/accesos-qr', [AccesoQrController::class, 'index'])
        ->middleware('permission:fotochecks_ver');
    Route::post('/accesos-qr/{trabajadorId}', [AccesoQrController::class, 'registrar'])
        ->middleware('permission:fotochecks_ver');

    // Plantilla
    Route::get('/plantilla-trabajadores', [TrabajadorController::class, 'plantilla'])
        ->middleware('permission:trabajadores_ver');

    // API Keys
    Route::middleware('permission:api_keys_ver')->group(function () {
        Route::get('/api-keys', [ApiKeyController::class, 'index']);
        Route::get('/api-keys/{id}', [ApiKeyController::class, 'show']);
    });
    Route::middleware('permission:api_keys_crear')->group(function () {
        Route::post('/api-keys', [ApiKeyController::class, 'store']);
    });
    Route::middleware('permission:api_keys_editar')->group(function () {
        Route::put('/api-keys/{id}', [ApiKeyController::class, 'update']);
        Route::post('/api-keys/{id}/toggle-estado', [ApiKeyController::class, 'toggleEstado']);
        Route::post('/api-keys/{id}/regenerar', [ApiKeyController::class, 'regenerar']);
    });
    Route::middleware('permission:api_keys_eliminar')->group(function () {
        Route::delete('/api-keys/{id}', [ApiKeyController::class, 'destroy']);
    });

    // Oficinas
    Route::apiResource('oficinas', OficinaController::class)
        ->middleware('permission:oficinas_ver');

    // Tipos de Solicitud
    Route::apiResource('tipo-solicitudes', TipoSolicitudController::class)
        ->middleware('permission:tipo_solicitudes_ver');

    // Solicitudes / Tickets
    Route::get('/solicitudes', [SolicitudController::class, 'index'])
        ->middleware('permission:solicitudes_ver');
    Route::get('/solicitudes/{id}', [SolicitudController::class, 'show'])
        ->middleware('permission:solicitudes_ver');
    Route::put('/solicitudes/{id}', [SolicitudController::class, 'update'])
        ->middleware('permission:solicitudes_editar');
    Route::post('/solicitudes/{id}/derivar', [SolicitudController::class, 'derivar'])
        ->middleware('permission:solicitudes_editar');
    Route::post('/solicitudes/{id}/resolver', [SolicitudController::class, 'resolver'])
        ->middleware('permission:solicitudes_editar');
    Route::post('/solicitudes/{id}/rechazar', [SolicitudController::class, 'rechazar'])
        ->middleware('permission:solicitudes_editar');

    // Adjuntos de solicitudes
    Route::get('/solicitudes/{id}/adjuntos/{filename}', [AttachmentController::class, 'show'])
        ->middleware('permission:solicitudes_ver');
});

// API Externa (autenticacion por API key)
Route::middleware(['api.key'])->prefix('ext')->group(function () {
    Route::post('/tickets', [ExternalTicketController::class, 'crearTicket']);
    Route::get('/tickets/{codigo}', [ExternalTicketController::class, 'consultarTicket']);
    Route::get('/persona/dni/{dni}', [ExternalTicketController::class, 'consultarPorDni']);
    Route::get('/tipo-solicitudes', [ExternalTicketController::class, 'listarTipos']);
});
