<?php

namespace App\Providers;

use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Modules\Auth\Repositories\EloquentAuthRepository;
use App\Modules\Analytics\Repositories\Contracts\AnalyticsRepositoryInterface;
use App\Modules\Analytics\Repositories\EloquentAnalyticsRepository;
use App\Modules\StudySessions\Repositories\Contracts\StudySessionRepositoryInterface;
use App\Modules\StudySessions\Repositories\EloquentStudySessionRepository;
use App\Modules\Technologies\Repositories\Contracts\TechnologyRepositoryInterface;
use App\Modules\Technologies\Repositories\EloquentTechnologyRepository;
use Illuminate\Contracts\Foundation\ExceptionHandler;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExceptionHandler::class, \App\Exceptions\Handler::class);
        $this->app->bind(AuthRepositoryInterface::class, EloquentAuthRepository::class);
        $this->app->bind(StudySessionRepositoryInterface::class, EloquentStudySessionRepository::class);
        $this->app->bind(TechnologyRepositoryInterface::class, EloquentTechnologyRepository::class);
        $this->app->bind(AnalyticsRepositoryInterface::class, EloquentAnalyticsRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom([
            database_path('migrations'),
            database_path('migrations/transactional'),
            database_path('migrations/analytics'),
        ]);
    }
}
