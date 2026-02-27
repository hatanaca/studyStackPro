<?php

use App\Jobs\GenerateWeeklySummaryJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new GenerateWeeklySummaryJob)
    ->weeklyOn(1, '03:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('queue:prune-old --hours=72')
    ->daily()
    ->at('04:00')
    ->withoutOverlapping()
    ->onOneServer();
