<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\V1\OAuthController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', function () {
    return ['app' => 'StudyTrack Pro API', 'docs' => '/api/v1'];
});

Route::middleware('throttle:health')->get('health', HealthController::class)->name('health');

// OAuth routes — precisam de sessão web (cookie HttpOnly).
// Ficam em web.php porque o callback do provider (Google/Discord) vem sem
// headers de frontend, então o statefulApi do grupo api não inicia a sessão.
// Middlewares de sessão adicionados explicitamente porque o Laravel 11 com
// statefulApi() aplica middlewares do grupo api a rotas web com prefixo api/.
Route::prefix('api/v1/auth')->middleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    'throttle:10,1',
])->name('v1.auth.')->group(function () {
    Route::get('/{provider}', [OAuthController::class, 'redirect'])
        ->where('provider', 'google|discord|linkedin')
        ->name('oauth.redirect');
    Route::get('/{provider}/callback', [OAuthController::class, 'callback'])
        ->where('provider', 'google|discord|linkedin')
        ->name('oauth.callback');
});
