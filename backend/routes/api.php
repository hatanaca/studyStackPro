<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\V1\AnalyticsController;
use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\CodeExecutionController;
use App\Http\Controllers\V1\LinkedInController;
use App\Http\Controllers\V1\StudySessionController;
use App\Http\Controllers\V1\TechnologyController;
use App\Http\Controllers\V1\YouTubeController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/**
 * Rotas da API e canais de broadcast (WebSocket).
 *
 * v1: prefixo de versão. Auth: register/login sem auth; demais rotas com auth:sanctum.
 * Throttle: login e register em rotas separadas (limites independentes); search, sensitive, recalculate, export, health.
 */
Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::prefix('v1')->name('v1.')->group(function () {
    // Registo e login: limitadores separados (register não deve herdar o limite mais apertado de login).
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:register');
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    // OAuth Routes movidas para web.php — precisam de sessão web completa
    // pois o callback do provider (Google/Discord/LinkedIn) não passa pelo statefulApi.

    Route::middleware(['auth:sanctum'])->group(function () {
        // User info endpoints
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('auth/me', [AuthController::class, 'me']);
            Route::get('auth/tokens', [AuthController::class, 'tokens']);
        });

        // Search endpoints - moderate throttling
        // YouTube API proxy (autenticado — não expõe a API key ao frontend)
        Route::middleware('throttle:search')->group(function () {
            Route::get('youtube/search', [YouTubeController::class, 'search'])
                ->name('youtube.search');
            Route::get('youtube/videos', [YouTubeController::class, 'videos'])
                ->name('youtube.videos');
            Route::get('youtube/playlists', [YouTubeController::class, 'playlists'])
                ->name('youtube.playlists');
        });

        // LinkedIn integration
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('linkedin/status', [LinkedInController::class, 'status'])
                ->name('linkedin.status');
            Route::post('linkedin/share', [LinkedInController::class, 'share'])
                ->name('linkedin.share');
            Route::post('linkedin/disconnect', [LinkedInController::class, 'disconnect'])
                ->name('linkedin.disconnect');
        });
        Route::middleware('throttle:search')->group(function () {
            Route::get('technologies/search', [TechnologyController::class, 'search'])
                ->name('technologies.search');
            Route::get('study-sessions/active', [StudySessionController::class, 'active'])
                ->name('study-sessions.active');
        });

        // Read operations
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('technologies', [TechnologyController::class, 'index']);
            Route::get('technologies/{technology}', [TechnologyController::class, 'show']);
            Route::get('study-sessions', [StudySessionController::class, 'index']);
            Route::get('study-sessions/{id}', [StudySessionController::class, 'show']);
            Route::prefix('analytics')->name('analytics.')->group(function () {
                Route::get('dashboard', [AnalyticsController::class, 'dashboard']);
                Route::get('user-metrics', [AnalyticsController::class, 'userMetrics']);
                Route::get('tech-stats', [AnalyticsController::class, 'techStats']);
                Route::get('time-series', [AnalyticsController::class, 'timeSeries']);
                Route::get('weekly', [AnalyticsController::class, 'weekly']);
                Route::get('heatmap', [AnalyticsController::class, 'heatmap']);
                Route::middleware('throttle:export')->group(function () {
                    Route::get('export', [AnalyticsController::class, 'export'])->name('export');
                });
            });
        });

        // Write operations
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('auth/logout', [AuthController::class, 'logout']);
            Route::put('auth/me', [AuthController::class, 'updateProfile']);
            Route::post('auth/change-password', [AuthController::class, 'changePassword'])
                ->middleware('throttle:sensitive');
            Route::delete('auth/tokens', [AuthController::class, 'revokeAllTokens']);
            Route::post('technologies', [TechnologyController::class, 'store']);
            Route::put('technologies/{technology}', [TechnologyController::class, 'update']);
            Route::delete('technologies/{technology}', [TechnologyController::class, 'destroy']);
            Route::post('study-sessions/start', [StudySessionController::class, 'start'])
                ->middleware('throttle.sliding:10')
                ->name('study-sessions.start');
            Route::post('study-sessions', [StudySessionController::class, 'store'])
                ->middleware('throttle.sliding:30');
            Route::patch('study-sessions/{id}/end', [StudySessionController::class, 'end'])
                ->middleware('throttle.sliding:10')
                ->name('study-sessions.end');
            Route::put('study-sessions/{id}', [StudySessionController::class, 'update'])
                ->middleware('throttle.sliding:30')
                ->name('study-sessions.put');
            Route::patch('study-sessions/{id}', [StudySessionController::class, 'update'])
                ->middleware('throttle.sliding:30')
                ->name('study-sessions.patch');
            Route::delete('study-sessions/{id}', [StudySessionController::class, 'destroy'])
                ->middleware('throttle.sliding:30');
            Route::post('analytics/recalculate', [AnalyticsController::class, 'recalculate'])
                ->middleware('throttle:recalculate');

            // Code execution terminal
            Route::middleware('throttle:10,1')->group(function () {
                Route::post('code/execute', [CodeExecutionController::class, 'execute'])
                    ->name('code.execute');
            });
            Route::get('code/languages', [CodeExecutionController::class, 'languages'])
                ->name('code.languages');
        });
    });
});

Route::middleware('throttle:health')->get('health', HealthController::class)->name('api.health');
