<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuestionnaireItemType;
use App\Models\QuestionnaireTemplate;
use App\Models\QuestionnaireTemplateItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionnaireTemplateItem>
 */
final class QuestionnaireTemplateItemFactory extends Factory
{
    protected $model = QuestionnaireTemplateItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'questionnaire_template_id' => QuestionnaireTemplate::factory(),
            'type' => QuestionnaireItemType::File,
            'title' => fake()->sentence(2),
            'instructions' => fake()->optional()->sentence(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
