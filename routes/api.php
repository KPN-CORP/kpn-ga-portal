<?php

use App\Http\Controllers\Api\ApiEmpHcisController;
use App\Http\Controllers\Api\ServiceScheduleController;
use App\Http\Controllers\Api\RepairController;
use App\Http\Controllers\Api\OperationalReportController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FuelLogController;

Route::prefix('integration')->group(function () {

    Route::get('/employees', [ApiEmpHcisController::class, 'index'])
        ->name('api.employees.index');

    Route::post('/employees/sync', [ApiEmpHcisController::class, 'sync'])
        ->middleware('auth:sanctum');
});

Route::middleware(['auth:sanctum', 'abilities:read', 'throttle:60,1'])->prefix('v1')->name('api.v1.')->group(function () {
    Route::get('service-schedules', [ServiceScheduleController::class, 'index'])->name('service-schedules.index');
    Route::get('service-schedules/{id}', [ServiceScheduleController::class, 'show'])->name('service-schedules.show');

    Route::get('repairs', [RepairController::class, 'index'])->name('repairs.index');
    Route::get('repairs/{id}', [RepairController::class, 'show'])->name('repairs.show');

    Route::get('fuel-logs', [FuelLogController::class, 'index'])->name('fuel-logs.index');
    Route::get('fuel-logs/{id}', [FuelLogController::class, 'show'])->name('fuel-logs.show');

    Route::get('trip-logs', [OperationalReportController::class, 'tripLogs'])->name('trip-logs.index');
});

Route::middleware(['auth:sanctum', 'abilities:read', 'throttle:20,1'])->prefix('v1')->name('api.v1.')->group(function () {
    Route::get('operational-summary', [OperationalReportController::class, 'summary'])->name('operational-summary');
});