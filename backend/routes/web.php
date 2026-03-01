<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['app' => 'StudyTrack Pro API', 'docs' => '/api/v1'];
});

Route::middleware('throttle:health')->get('health', HealthController::class)->name('health');
