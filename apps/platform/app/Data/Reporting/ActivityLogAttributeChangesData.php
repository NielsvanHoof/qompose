<?php

declare(strict_types=1);

namespace App\Data\Reporting;

/**
 * Activity log attribute diff payload.
 */
final readonly class ActivityLogAttributeChangesData
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $old
     */
    public function __construct(
        public array $attributes,
        public array $old,
    ) {}

    /**
     * @return array{attributes: array<string, mixed>, old: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'attributes' => $this->attributes,
            'old' => $this->old,
        ];
    }
}
