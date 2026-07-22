<?php

declare(strict_types=1);

namespace App\Data\Reporting;

/**
 * Compact activity log request metadata.
 */
final readonly class ActivityLogPropertiesData
{
    public function __construct(
        public ?string $ip,
        public ?string $route,
    ) {}

    /**
     * @return array{ip: string|null, route: string|null}
     */
    public function toArray(): array
    {
        return [
            'ip' => $this->ip,
            'route' => $this->route,
        ];
    }
}
