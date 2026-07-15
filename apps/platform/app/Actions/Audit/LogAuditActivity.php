<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Enums\AuditEvent;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class LogAuditActivity
{
    public function __construct(private Request $request) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public function __invoke(
        AuditEvent $event,
        ?Model $subject = null,
        array $properties = [],
        ?User $causer = null,
    ): Activity {
        $logger = activity(config('activitylog.default_log_name'))
            ->event($event->value)
            ->withProperties([...$this->requestContext(), ...$properties]);

        if ($subject instanceof Model) {
            $logger->performedOn($subject);
        }

        $causer ??= $this->request->user();

        if ($causer instanceof User) {
            $logger->causedBy($causer);
        }

        /** @var Activity $activity */
        $activity = $logger->log($event->label());

        return $activity;
    }

    /**
     * @return array<string, mixed>
     */
    private function requestContext(): array
    {
        return [
            'ip' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'route' => $this->request->route()?->getName(),
        ];
    }
}
