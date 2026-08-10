<?php

namespace App\Providers;

use App\Modules\Analytics\Repositories\Contracts\AnalyticsRepositoryInterface;
use App\Modules\Analytics\Repositories\EloquentAnalyticsRepository;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Modules\Auth\Repositories\EloquentAuthRepository;
use App\Modules\Canvas\Repositories\Contracts\CanvasRepositoryInterface;
use App\Modules\Canvas\Repositories\EloquentCanvasRepository;
use App\Modules\Gamification\Repositories\Contracts\AchievementRepositoryInterface;
use App\Modules\Gamification\Repositories\EloquentAchievementRepository;
use App\Modules\Goals\Repositories\Contracts\GoalRepositoryInterface;
use App\Modules\Goals\Repositories\EloquentGoalRepository;
use App\Modules\ItaStudy\Repositories\Contracts\StudyQuestionRepositoryInterface;
use App\Modules\ItaStudy\Repositories\Contracts\StudySubjectRepositoryInterface;
use App\Modules\ItaStudy\Repositories\Contracts\UserSubTopicProgressRepositoryInterface;
use App\Modules\ItaStudy\Repositories\EloquentStudyQuestionRepository;
use App\Modules\ItaStudy\Repositories\EloquentStudySubjectRepository;
use App\Modules\ItaStudy\Repositories\EloquentUserSubTopicProgressRepository;
use App\Modules\Notifications\Repositories\Contracts\NotificationRepositoryInterface;
use App\Modules\Notifications\Repositories\EloquentNotificationRepository;
use App\Modules\Reminders\Repositories\Contracts\ReminderRepositoryInterface;
use App\Modules\Reminders\Repositories\EloquentReminderRepository;
use App\Modules\StudyPaths\Repositories\Contracts\StudyPathRepositoryInterface;
use App\Modules\StudyPaths\Repositories\EloquentStudyPathRepository;
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
        $this->app->bind(GoalRepositoryInterface::class, EloquentGoalRepository::class);
        $this->app->bind(CanvasRepositoryInterface::class, EloquentCanvasRepository::class);
        $this->app->bind(StudyPathRepositoryInterface::class, EloquentStudyPathRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, EloquentNotificationRepository::class);
        $this->app->bind(ReminderRepositoryInterface::class, EloquentReminderRepository::class);
        $this->app->bind(AchievementRepositoryInterface::class, EloquentAchievementRepository::class);
        $this->app->bind(StudySubjectRepositoryInterface::class, EloquentStudySubjectRepository::class);
        $this->app->bind(StudyQuestionRepositoryInterface::class, EloquentStudyQuestionRepository::class);
        $this->app->bind(UserSubTopicProgressRepositoryInterface::class, EloquentUserSubTopicProgressRepository::class);
    }
}
