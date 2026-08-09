<?php

use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\MathServiceProvider;
use App\Providers\RedisScriptServiceProvider;
use App\Providers\RepositoryServiceProvider;
use App\Providers\YouTubeServiceProvider;

return [
    AppServiceProvider::class,
    EventServiceProvider::class,
    MathServiceProvider::class,
    RepositoryServiceProvider::class,
    RedisScriptServiceProvider::class,
    YouTubeServiceProvider::class,
];
