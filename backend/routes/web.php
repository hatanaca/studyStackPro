<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

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
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
])->name('v1.auth.')->group(function () {
    Route::get('/{provider}', [\App\Http\Controllers\V1\OAuthController::class, 'redirect'])
        ->where('provider', 'google|discord')
        ->name('oauth.redirect');
    Route::get('/{provider}/callback', [\App\Http\Controllers\V1\OAuthController::class, 'callback'])
        ->where('provider', 'google|discord')
        ->name('oauth.callback');
});
