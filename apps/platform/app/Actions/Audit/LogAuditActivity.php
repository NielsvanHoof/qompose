<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Enums\AuditEvent;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

use function is_array;
use function is_string;

final class LogAuditActivity
{
    public function __construct(private Request $request) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public function handle(
        AuditEvent $event,
        ?Model $subject = null,
        array $properties = [],
        ?User $causer = null,
        bool $includeRequestContext = true,
    ): Activity {
        $context = $includeRequestContext ? $this->requestContext() : [];

        $logger = activity(config('activitylog.default_log_name'))
            ->event($event->value)
            ->withProperties($this->withoutSensitiveProperties([...$context, ...$properties]));

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
        $userAgent = $this->request->userAgent();

        return [
            'ip' => $this->request->ip(),
            'user_agent' => is_string($userAgent) ? mb_substr($userAgent, 0, 512) : null,
            'route' => $this->request->route()?->getName(),
        ];
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function withoutSensitiveProperties(array $properties): array
    {
        $safeProperties = [];

        foreach ($properties as $key => $value) {
            if ($this->isSensitiveProperty($key)) {
                continue;
            }

            $safeProperties[$key] = is_array($value)
                ? $this->withoutSensitiveProperties($value)
                : $value;
        }

        return $safeProperties;
    }

    private function isSensitiveProperty(string $key): bool
    {
        $normalizedKey = mb_strtolower($key);

        return str_contains($normalizedKey, 'token')
            || str_contains($normalizedKey, 'portal_url')
            || str_contains($normalizedKey, 'magic_link')
            || str_contains($normalizedKey, 'password')
            || str_contains($normalizedKey, 'secret')
            || str_contains($normalizedKey, 'authorization')
            || str_contains($normalizedKey, 'cookie');
    }
}
