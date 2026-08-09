<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\V1\AchievementController;
use App\Http\Controllers\V1\AnalyticsController;
use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\CanvasController;
use App\Http\Controllers\V1\CodeExecutionController;
use App\Http\Controllers\V1\ExerciseController;
use App\Http\Controllers\V1\FlashcardController;
use App\Http\Controllers\V1\ItaStudyController;
use App\Http\Controllers\V1\GoalController;
use App\Http\Controllers\V1\LinkedInController;
use App\Http\Controllers\V1\NotificationController;
use App\Http\Controllers\V1\OAuthController;
use App\Http\Controllers\V1\ReminderController;
use App\Http\Controllers\V1\StudyPathController;
use App\Http\Controllers\V1\StudySessionController;
use App\Http\Controllers\V1\TechnologyController;
use App\Http\Controllers\V1\UserStudyController;
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
    Route::post('auth/oauth-complete', [OAuthController::class, 'oauthComplete'])
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

            // Goals
            Route::apiResource('goals', GoalController::class)
                ->middleware('throttle:30,1');

            // Canvas
            Route::apiResource('canvas', CanvasController::class)
                ->middleware('throttle:30,1');

            // Study Paths
            Route::apiResource('study-paths', StudyPathController::class)
                ->middleware('throttle:30,1');
            Route::get('study-paths/technology/{technologyId}', [StudyPathController::class, 'byTechnology'])
                ->name('study-paths.by-technology');

            // Notifications
            Route::get('notifications', [NotificationController::class, 'index'])
                ->middleware('throttle:60,1');
            Route::post('notifications', [NotificationController::class, 'store'])
                ->middleware('throttle:30,1');
            Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])
                ->middleware('throttle:60,1');
            Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])
                ->middleware('throttle:30,1');
            Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])
                ->middleware('throttle:30,1');
            Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])
                ->middleware('throttle:60,1');

            // Reminders
            Route::apiResource('reminders', ReminderController::class)
                ->middleware('throttle:30,1');

            // Achievements (Gamification)
            Route::get('achievements', [AchievementController::class, 'index'])
                ->middleware('throttle:60,1');
            Route::post('achievements/check', [AchievementController::class, 'check'])
                ->middleware('throttle:10,1');

            // Code execution terminal
            Route::middleware('throttle:10,1')->group(function () {
                Route::post('code/execute', [CodeExecutionController::class, 'execute'])
                    ->name('code.execute');
            });
            Route::get('code/languages', [CodeExecutionController::class, 'languages'])
                ->middleware('throttle:60,1')
                ->name('code.languages');

            // Exercises (matemática)
            Route::apiResource('exercises/templates', ExerciseController::class)
                ->middleware('throttle:30,1');
            Route::post('exercises/templates/{template}/generate', [ExerciseController::class, 'generate'])
                ->middleware('throttle:generate')
                ->name('exercises.generate');
            Route::get('exercises/attempts', [ExerciseController::class, 'attempts'])
                ->middleware('throttle:60,1')
                ->name('exercises.attempts');
            Route::get('exercises/stats', [ExerciseController::class, 'stats'])
                ->middleware('throttle:60,1')
                ->name('exercises.stats');
            Route::post('exercises/grade', [ExerciseController::class, 'grade'])
                ->middleware('throttle:grade')
                ->name('exercises.grade');
            Route::post('exercises/solve', [ExerciseController::class, 'solve'])
                ->middleware('throttle:grade')
                ->name('exercises.solve');

            // Flashcards (repetição espaçada FSRS)
            Route::apiResource('flashcard-decks', FlashcardController::class)
                ->middleware('throttle:30,1');
            Route::get('flashcard-decks/{deck}/cards', [FlashcardController::class, 'cards'])
                ->middleware('throttle:60,1')
                ->name('flashcards.cards');
            Route::post('flashcard-decks/{deck}/cards', [FlashcardController::class, 'storeCard'])
                ->middleware('throttle:30,1')
                ->name('flashcards.store-card');
            Route::delete('flashcards/{flashcard}', [FlashcardController::class, 'destroyCard'])
                ->middleware('throttle:30,1')
                ->name('flashcards.destroy-card');
            Route::get('flashcards/due', [FlashcardController::class, 'due'])
                ->middleware('throttle:60,1')
                ->name('flashcards.due');
            Route::post('flashcards/{flashcard}/review', [FlashcardController::class, 'review'])
                ->middleware('throttle:review')
                ->name('flashcards.review');

            // ITA Study (checklist de estudo)
            Route::prefix('ita-study')->name('ita-study.')->group(function () {
                Route::get('subjects', [ItaStudyController::class, 'subjects'])
                    ->name('subjects');
                Route::get('subjects/{subjectId}/topics', [ItaStudyController::class, 'topics'])
                    ->name('topics');
                Route::get('topics/{topicId}/subtopics', [ItaStudyController::class, 'subTopics'])
                    ->name('sub-topics');
                Route::post('questions/generate', [ItaStudyController::class, 'generate'])
                    ->middleware('throttle:generate')
                    ->name('questions.generate');
                Route::post('questions/answer', [ItaStudyController::class, 'answer'])
                    ->middleware('throttle:grade')
                    ->name('questions.answer');
                Route::post('questions/generate-batch', [ItaStudyController::class, 'generateBatch'])
                    ->middleware('throttle:generate')
                    ->name('questions.generate-batch');
                Route::get('progress', [ItaStudyController::class, 'progress'])
                    ->name('progress');
                Route::get('progress/subject/{subjectId}', [ItaStudyController::class, 'subjectProgress'])
                    ->name('progress.subject');
                Route::get('progress/topic/{topicId}', [ItaStudyController::class, 'topicProgress'])
                    ->name('progress.topic');

                // Página completa de estudo: conteúdo, simulação e interações do usuário
                Route::get('subtopics/{subTopicId}', [UserStudyController::class, 'subTopic'])
                    ->name('sub-topics.detail');
                Route::get('favorites', [UserStudyController::class, 'favorites'])
                    ->name('favorites');
                Route::post('favorites', [UserStudyController::class, 'addFavorite'])
                    ->name('favorites.store');
                Route::delete('favorites/{subTopicId}', [UserStudyController::class, 'removeFavorite'])
                    ->name('favorites.destroy');
                Route::get('subtopics/{subTopicId}/note', [UserStudyController::class, 'getNote'])
                    ->name('notes.show');
                Route::put('subtopics/{subTopicId}/note', [UserStudyController::class, 'saveNote'])
                    ->name('notes.update');
                Route::delete('subtopics/{subTopicId}/note', [UserStudyController::class, 'deleteNote'])
                    ->name('notes.destroy');
                Route::get('subtopics/{subTopicId}/reading-progress', [UserStudyController::class, 'getReadingProgress'])
                    ->name('reading-progress.show');
                Route::put('subtopics/{subTopicId}/reading-progress', [UserStudyController::class, 'updateReadingProgress'])
                    ->name('reading-progress.update');
            });
        });
    });
});

Route::middleware('throttle:health')->get('health', HealthController::class)->name('api.health');
