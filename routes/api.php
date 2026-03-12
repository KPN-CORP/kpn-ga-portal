use App\Http\Controllers\Api\ApiEmpHcisController;

Route::prefix('integration')->group(function () {

    Route::get('/employees', [ApiEmpHcisController::class, 'index'])
        ->name('api.employees.index');

    Route::post('/employees/sync', [ApiEmpHcisController::class, 'sync'])
        ->middleware('auth:sanctum');
});