<?php

declare(strict_types=1);

namespace App\Data\Tenancy;

/**
 * Workspace switcher entry shared via Inertia.
 */
final readonly class WorkspaceNavItemData
{
    public function __construct(
        public string $name,
        public string $slug,
    ) {}

    /**
     * @return array{name: string, slug: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }
}
