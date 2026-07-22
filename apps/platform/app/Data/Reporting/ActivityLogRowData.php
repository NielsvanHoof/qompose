<?php

declare(strict_types=1);

namespace App\Data\Reporting;

/**
 * Activity log index table row.
 */
final readonly class ActivityLogRowData
{
    public function __construct(
        public int $id,
        public ?string $event,
        public string $label,
        public string $description,
        public ?string $causerName,
        public ?ActivityLogSubjectData $subject,
        public ?string $createdAt,
        public ActivityLogPropertiesData $properties,
        public ?ActivityLogAttributeChangesData $attributeChanges,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     event: string|null,
     *     label: string,
     *     description: string,
     *     causer_name: string|null,
     *     subject: array{type: string, id: int, name: string|null}|null,
     *     created_at: string|null,
     *     properties: array{ip: string|null, route: string|null},
     *     attribute_changes: array{attributes: array<string, mixed>, old: array<string, mixed>}|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'label' => $this->label,
            'description' => $this->description,
            'causer_name' => $this->causerName,
            'subject' => $this->subject?->toArray(),
            'created_at' => $this->createdAt,
            'properties' => $this->properties->toArray(),
            'attribute_changes' => $this->attributeChanges?->toArray(),
        ];
    }
}
