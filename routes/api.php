<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes  (prefix: /api  — set in bootstrap/app.php)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {

    // ── Auth (public) ───────────────────────────────────────────────────
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login',    [AuthController::class, 'login'])->name('login');
    });

    // ── Protected (Sanctum token required) ──────────────────────────────
    Route::middleware('auth:sanctum')->group(function (): void {

        // Auth
        Route::prefix('auth')->name('auth.')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('me',      [AuthController::class, 'me'])->name('me');
        });

        // Tasks — full RESTful resource
        // GET    /api/v1/tasks          → index
        // POST   /api/v1/tasks          → store
        // GET    /api/v1/tasks/{task}   → show
        // PUT    /api/v1/tasks/{task}   → update
        // PATCH  /api/v1/tasks/{task}   → update (partial)
        // DELETE /api/v1/tasks/{task}   → destroy
        Route::apiResource('tasks', TaskController::class)->parameters(['tasks' => 'id']);
        Route::patch('tasks/{id}', [TaskController::class, 'update'])->name('api.v1.tasks.patch');
    });
});
