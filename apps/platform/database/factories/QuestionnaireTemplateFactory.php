<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuestionnaireTemplateCategory;
use App\Models\QuestionnaireTemplate;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionnaireTemplate>
 */
final class QuestionnaireTemplateFactory extends Factory
{
    protected $model = QuestionnaireTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'category' => QuestionnaireTemplateCategory::Custom,
            'source_template_id' => null,
        ];
    }

    /**
     * Platform-wide read-only system template.
     */
    public function system(): static
    {
        return $this->state(fn (): array => [
            'tenant_id' => null,
            'category' => QuestionnaireTemplateCategory::Kyc,
        ]);
    }
}
