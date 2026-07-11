<?php

use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\RedisScriptServiceProvider;
use App\Providers\RepositoryServiceProvider;
use App\Providers\YouTubeServiceProvider;

return [
    AppServiceProvider::class,
    EventServiceProvider::class,
    RepositoryServiceProvider::class,
    RedisScriptServiceProvider::class,
    YouTubeServiceProvider::class,
];
