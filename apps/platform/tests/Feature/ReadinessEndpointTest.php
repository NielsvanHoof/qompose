<?php

declare(strict_types=1);

use App\Contracts\Production\ChecksReadiness;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Facades\Health;

test('readiness succeeds when production dependencies are available', function (): void {
    app()->bind(ChecksReadiness::class, fn (): ChecksReadiness => new class implements ChecksReadiness
    {
        public function check(): void {}
    });

    $this->getJson('/ready')
        ->assertOk()
        ->assertExactJson(['status' => 'ready']);
});

test('readiness fails closed when a production dependency is unavailable', function (): void {
    app()->bind(ChecksReadiness::class, fn (): ChecksReadiness => new class implements ChecksReadiness
    {
        public function check(): void
        {
            throw new RuntimeException('Sensitive connection failure.');
        }
    });

    $this->getJson('/ready')
        ->assertServiceUnavailable()
        ->assertExactJson(['status' => 'unavailable']);
});

test('liveness does not depend on production dependencies', function (): void {
    app()->bind(ChecksReadiness::class, fn (): ChecksReadiness => new class implements ChecksReadiness
    {
        public function check(): void
        {
            throw new RuntimeException('Dependency unavailable.');
        }
    });

    $this->get('/up')->assertOk();
});

test('operational health registers infrastructure and process heartbeat checks', function (): void {
    $registeredChecks = Health::registeredChecks();

    expect($registeredChecks->contains(fn ($check): bool => $check instanceof DatabaseCheck))->toBeTrue()
        ->and($registeredChecks->contains(fn ($check): bool => $check instanceof RedisCheck))->toBeTrue()
        ->and($registeredChecks->contains(fn ($check): bool => $check instanceof QueueCheck))->toBeTrue()
        ->and($registeredChecks->contains(fn ($check): bool => $check instanceof ScheduleCheck))->toBeTrue();
});

test('operational health details require authentication', function (): void {
    $this->getJson('/health')->assertUnauthorized();
});

test('queue schedule and health heartbeats are scheduled every minute', function (): void {
    $commands = collect(app(Schedule::class)->events())
        ->map(fn ($event): string => (string) $event->command)
        ->implode(' ');

    expect($commands)
        ->toContain('health:queue-check-heartbeat')
        ->toContain('health:schedule-check-heartbeat')
        ->toContain('health:check');
});

test('retention and pruning tasks are scheduled', function (): void {
    $commands = collect(app(Schedule::class)->events())
        ->map(fn ($event): string => (string) $event->command)
        ->implode(' ');

    expect($commands)
        ->toContain('queue:prune-failed')
        ->toContain('queue:prune-batches')
        ->toContain('model:prune')
        ->toContain('retention:prune-notifications')
        ->toContain('retention:purge-legal')
        ->toContain('activitylog:clean');
});
