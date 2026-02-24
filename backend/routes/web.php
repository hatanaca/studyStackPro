<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['app' => 'StudyTrack Pro API', 'docs' => '/api/v1'];
});
