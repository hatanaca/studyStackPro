<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::prefix('v1')->name('v1.')->group(function () {
    Route::middleware('throttle:auth')->group(function () {
        Route::post('auth/register', [\App\Http\Controllers\V1\AuthController::class, 'register']);
        Route::post('auth/login', [\App\Http\Controllers\V1\AuthController::class, 'login']);
    });

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('auth/me', [\App\Http\Controllers\V1\AuthController::class, 'me']);
            Route::get('auth/tokens', [\App\Http\Controllers\V1\AuthController::class, 'tokens']);
        });
        Route::middleware('throttle:search')->group(function () {
            Route::get('technologies/search', [\App\Http\Controllers\V1\TechnologyController::class, 'search'])
                ->name('technologies.search');
            Route::get('study-sessions/active', [\App\Http\Controllers\V1\StudySessionController::class, 'active'])
                ->name('study-sessions.active');
        });
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('technologies', [\App\Http\Controllers\V1\TechnologyController::class, 'index']);
            Route::get('technologies/{technology}', [\App\Http\Controllers\V1\TechnologyController::class, 'show']);
            Route::get('study-sessions', [\App\Http\Controllers\V1\StudySessionController::class, 'index']);
            Route::get('study-sessions/{id}', [\App\Http\Controllers\V1\StudySessionController::class, 'show']);
            Route::prefix('analytics')->name('analytics.')->group(function () {
                Route::get('dashboard', [\App\Http\Controllers\V1\AnalyticsController::class, 'dashboard']);
                Route::get('user-metrics', [\App\Http\Controllers\V1\AnalyticsController::class, 'userMetrics']);
                Route::get('tech-stats', [\App\Http\Controllers\V1\AnalyticsController::class, 'techStats']);
                Route::get('time-series', [\App\Http\Controllers\V1\AnalyticsController::class, 'timeSeries']);
                Route::get('weekly', [\App\Http\Controllers\V1\AnalyticsController::class, 'weekly']);
                Route::get('heatmap', [\App\Http\Controllers\V1\AnalyticsController::class, 'heatmap']);
                Route::middleware('throttle:export')->group(function () {
                    Route::get('export', [\App\Http\Controllers\V1\AnalyticsController::class, 'export'])->name('export');
                });
            });
        });

        Route::middleware('throttle:30,1')->group(function () {
            Route::post('auth/logout', [\App\Http\Controllers\V1\AuthController::class, 'logout']);
            Route::put('auth/me', [\App\Http\Controllers\V1\AuthController::class, 'updateProfile']);
            Route::post('auth/change-password', [\App\Http\Controllers\V1\AuthController::class, 'changePassword'])
                ->middleware('throttle:sensitive');
            Route::delete('auth/tokens', [\App\Http\Controllers\V1\AuthController::class, 'revokeAllTokens']);
            Route::post('technologies', [\App\Http\Controllers\V1\TechnologyController::class, 'store']);
            Route::put('technologies/{technology}', [\App\Http\Controllers\V1\TechnologyController::class, 'update']);
            Route::delete('technologies/{technology}', [\App\Http\Controllers\V1\TechnologyController::class, 'destroy']);
            Route::post('study-sessions/start', [\App\Http\Controllers\V1\StudySessionController::class, 'start'])
                ->name('study-sessions.start');
            Route::post('study-sessions', [\App\Http\Controllers\V1\StudySessionController::class, 'store']);
            Route::patch('study-sessions/{id}/end', [\App\Http\Controllers\V1\StudySessionController::class, 'end'])
                ->name('study-sessions.end');
            Route::put('study-sessions/{id}', [\App\Http\Controllers\V1\StudySessionController::class, 'update'])
                ->name('study-sessions.put');
            Route::patch('study-sessions/{id}', [\App\Http\Controllers\V1\StudySessionController::class, 'update'])
                ->name('study-sessions.patch');
            Route::delete('study-sessions/{id}', [\App\Http\Controllers\V1\StudySessionController::class, 'destroy']);
            Route::post('analytics/recalculate', [\App\Http\Controllers\V1\AnalyticsController::class, 'recalculate'])
                ->middleware('throttle:recalculate');
        });
    });
});

Route::middleware('throttle:health')->get('health', \App\Http\Controllers\HealthController::class)->name('api.health');
