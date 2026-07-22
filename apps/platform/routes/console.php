<?php

declare(strict_types=1);

use App\Models\ClientAccessGrant;
use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\DispatchQueueCheckJobsCommand;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;

Schedule::command(DispatchQueueCheckJobsCommand::class)->everyMinute()->withoutOverlapping();
Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyMinute()->withoutOverlapping();
Schedule::command(RunHealthChecksCommand::class)->everyMinute()->withoutOverlapping();

Schedule::command('queue:prune-failed', [
    '--hours' => config('retention.failed_jobs_hours'),
])->daily()->at('02:00')->withoutOverlapping();

Schedule::command('queue:prune-batches', [
    '--hours' => config('retention.job_batches_hours'),
])->daily()->at('02:00')->withoutOverlapping();

Schedule::command('model:prune', [
    '--model' => [ClientAccessGrant::class],
])->daily()->at('02:00')->withoutOverlapping();

Schedule::command('retention:prune-notifications')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping();

Schedule::command('retention:purge-legal')
    ->daily()
    ->at('02:30')
    ->withoutOverlapping();

Schedule::command('activitylog:clean')
    ->weeklyOn(0, '03:00')
    ->withoutOverlapping();
