<?php

declare(strict_types=1);

namespace App\Data\Dossiers;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Full dossier show Inertia payload (templates + dossier).
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class DossierShowPageData implements Arrayable
{
    /**
     * @param  list<QuestionnaireTemplateOptionData>  $templates
     */
    public function __construct(
        public array $templates,
        public DossierShowData $dossier,
    ) {}

    /**
     * @return array{
     *     templates: list<array{
     *         id: int,
     *         name: string,
     *         category_label: string,
     *         items_count: int,
     *         is_system: bool
     *     }>,
     *     dossier: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'templates' => array_map(
                static fn (QuestionnaireTemplateOptionData $template): array => $template->toArray(),
                $this->templates,
            ),
            'dossier' => $this->dossier->toArray(),
        ];
    }
}
