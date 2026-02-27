<?php

namespace App\Providers;

use App\Modules\Analytics\Repositories\Contracts\AnalyticsRepositoryInterface;
use App\Modules\Analytics\Repositories\EloquentAnalyticsRepository;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Modules\Auth\Repositories\EloquentAuthRepository;
use App\Modules\StudySessions\Repositories\Contracts\StudySessionRepositoryInterface;
use App\Modules\StudySessions\Repositories\EloquentStudySessionRepository;
use App\Modules\Technologies\Repositories\Contracts\TechnologyRepositoryInterface;
use App\Modules\Technologies\Repositories\EloquentTechnologyRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthRepositoryInterface::class, EloquentAuthRepository::class);
        $this->app->bind(StudySessionRepositoryInterface::class, EloquentStudySessionRepository::class);
        $this->app->bind(TechnologyRepositoryInterface::class, EloquentTechnologyRepository::class);
        $this->app->bind(AnalyticsRepositoryInterface::class, EloquentAnalyticsRepository::class);
    }
}
