<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {
    Route::post('auth/register', [\App\Http\Controllers\V1\AuthController::class, 'register']);
    Route::post('auth/login', [\App\Http\Controllers\V1\AuthController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('auth/logout', [\App\Http\Controllers\V1\AuthController::class, 'logout']);
        Route::get('auth/me', [\App\Http\Controllers\V1\AuthController::class, 'me']);
        Route::put('auth/me', [\App\Http\Controllers\V1\AuthController::class, 'updateProfile']);
        Route::post('auth/change-password', [\App\Http\Controllers\V1\AuthController::class, 'changePassword']);
        Route::get('auth/tokens', [\App\Http\Controllers\V1\AuthController::class, 'tokens']);
        Route::delete('auth/tokens', [\App\Http\Controllers\V1\AuthController::class, 'revokeAllTokens']);

        Route::get('technologies/search', [\App\Http\Controllers\V1\TechnologyController::class, 'search'])
            ->name('technologies.search');
        Route::apiResource('technologies', \App\Http\Controllers\V1\TechnologyController::class);
        Route::get('study-sessions/active', [\App\Http\Controllers\V1\StudySessionController::class, 'active'])
            ->name('study-sessions.active');
        Route::post('study-sessions/start', [\App\Http\Controllers\V1\StudySessionController::class, 'start'])
            ->name('study-sessions.start');
        Route::patch('study-sessions/{id}/end', [\App\Http\Controllers\V1\StudySessionController::class, 'end'])
            ->name('study-sessions.end');
        Route::patch('study-sessions/{study_session}', [\App\Http\Controllers\V1\StudySessionController::class, 'update'])->name('study-sessions.patch');
        Route::apiResource('study-sessions', \App\Http\Controllers\V1\StudySessionController::class);

        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('dashboard', [\App\Http\Controllers\V1\AnalyticsController::class, 'dashboard']);
            Route::get('user-metrics', [\App\Http\Controllers\V1\AnalyticsController::class, 'userMetrics']);
            Route::get('tech-stats', [\App\Http\Controllers\V1\AnalyticsController::class, 'techStats']);
            Route::get('time-series', [\App\Http\Controllers\V1\AnalyticsController::class, 'timeSeries']);
            Route::get('weekly', [\App\Http\Controllers\V1\AnalyticsController::class, 'weekly']);
            Route::get('heatmap', [\App\Http\Controllers\V1\AnalyticsController::class, 'heatmap']);
            Route::post('recalculate', [\App\Http\Controllers\V1\AnalyticsController::class, 'recalculate']);
        });
    });
});

Route::get('health', function () {
    return response()->json([
        'status' => 'healthy',
        'services' => [
            'database' => 'ok',
            'redis' => 'ok',
        ],
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('health');
