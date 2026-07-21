<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\DispatchQueueCheckJobsCommand;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;

Schedule::command(DispatchQueueCheckJobsCommand::class)->everyMinute()->withoutOverlapping();
Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyMinute()->withoutOverlapping();
Schedule::command(RunHealthChecksCommand::class)->everyMinute()->withoutOverlapping();
