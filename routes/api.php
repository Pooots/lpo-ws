<?php

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\TalentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TalentCvController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Modular feature routes live under routes/routes/*.php and should be
| included here as modules are added.
|
*/

Route::get('/', function () {
    return response()->json([
        'ok' => true,
        'service' => 'lpo-api',
        'name' => 'LPO',
        'version' => '1.0.0',
        'health' => url('/api/health'),
        'v1' => url('/api/v1'),
    ]);
});

Route::get('/health', function () {
    $database = [
        'status' => 'ok',
        'driver' => config('database.default'),
        'name' => null,
        'version' => null,
        'migrations' => null,
        'latency_ms' => null,
    ];

    try {
        $started = microtime(true);
        $connection = DB::connection();
        $connection->getPdo();
        $connection->select('select 1');
        $database['latency_ms'] = round((microtime(true) - $started) * 1000, 2);
        $database['name'] = $connection->getDatabaseName();
        $database['version'] = $connection->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
        $database['migrations'] = Schema::hasTable('migrations')
            ? DB::table('migrations')->count()
            : 0;
    } catch (\Throwable $e) {
        $database['status'] = 'error';
        $database['error'] = config('app.debug') ? $e->getMessage() : 'Database connection failed';
    }

    $ok = $database['status'] === 'ok';

    return response()->json([
        'ok' => $ok,
        'service' => 'lpo-api',
        'status' => $ok ? 'healthy' : 'degraded',
        'app' => config('app.name'),
        'env' => config('app.env'),
        'database' => $database,
        'time' => now()->toIso8601String(),
        'php' => PHP_VERSION,
        'laravel' => app()->version(),
    ], $ok ? 200 : 503);
});

Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'ok' => true,
            'service' => 'lpo-api',
            'version' => 'v1',
            'health' => url('/api/health'),
        ]);
    });

    Route::prefix('auth')->group(function () {
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('candidate/register', [AuthController::class, 'registerCandidate']);
            Route::post('business/register', [AuthController::class, 'registerBusiness']);
            Route::post('login', [AuthController::class, 'login']);
        });

        Route::middleware('auth:api')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware(['auth:api', 'user.type:talent'])->prefix('talent')->group(function () {
        Route::post('cv', [TalentCvController::class, 'store'])->middleware('throttle:10,1');
    });

    Route::middleware(['auth:api', 'user.type:admin'])->prefix('admin')->group(function () {
        Route::get('talents', [TalentController::class, 'index']);
        Route::post('talents', [TalentController::class, 'store']);
        Route::get('talents/{talent}', [TalentController::class, 'show'])->whereNumber('talent');
        Route::patch('talents/{talent}/status', [TalentController::class, 'updateStatus'])->whereNumber('talent');
        Route::get('talents/{talent}/cv', [TalentController::class, 'downloadCv'])->whereNumber('talent');
        Route::post('talents/{talent}/cv', [TalentController::class, 'uploadCv'])->whereNumber('talent');

        Route::get('clients', [ClientController::class, 'index']);
        Route::post('clients', [ClientController::class, 'store']);
        Route::get('clients/{client}', [ClientController::class, 'show'])->whereNumber('client');
        Route::put('clients/{client}', [ClientController::class, 'update'])->whereNumber('client');
        Route::patch('clients/{client}/status', [ClientController::class, 'updateStatus'])->whereNumber('client');
    });
});
