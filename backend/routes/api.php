<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::prefix('v1')->name('v1.')->group(function () {
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('auth/register', [\App\Http\Controllers\V1\AuthController::class, 'register']);
        Route::post('auth/login', [\App\Http\Controllers\V1\AuthController::class, 'login']);
    });

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('auth/me', [\App\Http\Controllers\V1\AuthController::class, 'me']);
            Route::get('auth/tokens', [\App\Http\Controllers\V1\AuthController::class, 'tokens']);
            Route::get('technologies/search', [\App\Http\Controllers\V1\TechnologyController::class, 'search'])
                ->name('technologies.search');
            Route::get('technologies', [\App\Http\Controllers\V1\TechnologyController::class, 'index']);
            Route::get('technologies/{technology}', [\App\Http\Controllers\V1\TechnologyController::class, 'show']);
            Route::get('study-sessions/active', [\App\Http\Controllers\V1\StudySessionController::class, 'active'])
                ->name('study-sessions.active');
            Route::get('study-sessions', [\App\Http\Controllers\V1\StudySessionController::class, 'index']);
            Route::get('study-sessions/{id}', [\App\Http\Controllers\V1\StudySessionController::class, 'show']);
            Route::prefix('analytics')->name('analytics.')->group(function () {
                Route::get('dashboard', [\App\Http\Controllers\V1\AnalyticsController::class, 'dashboard']);
                Route::get('user-metrics', [\App\Http\Controllers\V1\AnalyticsController::class, 'userMetrics']);
                Route::get('tech-stats', [\App\Http\Controllers\V1\AnalyticsController::class, 'techStats']);
                Route::get('time-series', [\App\Http\Controllers\V1\AnalyticsController::class, 'timeSeries']);
                Route::get('weekly', [\App\Http\Controllers\V1\AnalyticsController::class, 'weekly']);
                Route::get('heatmap', [\App\Http\Controllers\V1\AnalyticsController::class, 'heatmap']);
            });
        });

        Route::middleware('throttle:30,1')->group(function () {
            Route::post('auth/logout', [\App\Http\Controllers\V1\AuthController::class, 'logout']);
            Route::put('auth/me', [\App\Http\Controllers\V1\AuthController::class, 'updateProfile']);
            Route::post('auth/change-password', [\App\Http\Controllers\V1\AuthController::class, 'changePassword']);
            Route::delete('auth/tokens', [\App\Http\Controllers\V1\AuthController::class, 'revokeAllTokens']);
            Route::post('technologies', [\App\Http\Controllers\V1\TechnologyController::class, 'store']);
            Route::put('technologies/{technology}', [\App\Http\Controllers\V1\TechnologyController::class, 'update']);
            Route::delete('technologies/{technology}', [\App\Http\Controllers\V1\TechnologyController::class, 'destroy']);
            Route::post('study-sessions/start', [\App\Http\Controllers\V1\StudySessionController::class, 'start'])
                ->name('study-sessions.start');
            Route::post('study-sessions', [\App\Http\Controllers\V1\StudySessionController::class, 'store']);
            Route::patch('study-sessions/{id}/end', [\App\Http\Controllers\V1\StudySessionController::class, 'end'])
                ->name('study-sessions.end');
            Route::patch('study-sessions/{study_session}', [\App\Http\Controllers\V1\StudySessionController::class, 'update'])
                ->name('study-sessions.patch');
            Route::delete('study-sessions/{study_session}', [\App\Http\Controllers\V1\StudySessionController::class, 'destroy']);
            Route::post('analytics/recalculate', [\App\Http\Controllers\V1\AnalyticsController::class, 'recalculate']);
        });
    });
});

Route::get('health', function () {
    $services = [];
    $healthy = true;

    try {
        DB::connection()->getPdo();
        $services['database'] = 'ok';
    } catch (\Throwable $e) {
        $services['database'] = 'error';
        $healthy = false;
    }

    try {
        Redis::ping();
        $services['redis'] = 'ok';
    } catch (\Throwable $e) {
        $services['redis'] = 'error';
        $healthy = false;
    }

    try {
        $queueConn = config('queue.default');
        $services['queue'] = $queueConn === 'redis' ? 'ok' : $queueConn;
    } catch (\Throwable $e) {
        $services['queue'] = 'error';
    }

    $reverbHost = config('broadcasting.connections.reverb.options.host', 'localhost');
    $reverbPort = config('broadcasting.connections.reverb.options.port', 8080);
    try {
        $socket = @fsockopen($reverbHost, (int) $reverbPort, $errno, $errstr, 2);
        $services['reverb'] = $socket ? 'ok' : 'error';
        if ($socket) {
            fclose($socket);
        }
    } catch (\Throwable $e) {
        $services['reverb'] = 'error';
    }

    return response()->json([
        'status' => $healthy ? 'healthy' : 'degraded',
        'services' => $services,
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ], $healthy ? 200 : 503);
})->name('health');
