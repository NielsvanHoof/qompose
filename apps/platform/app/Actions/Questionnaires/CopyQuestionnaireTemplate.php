<?php

declare(strict_types=1);

namespace App\Actions\Questionnaires;

use App\Models\QuestionnaireTemplate;
use App\Models\QuestionnaireTemplateItem;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CopyQuestionnaireTemplate
{
    /**
     * Deep-clone a system or firm template into the current tenant.
     */
    public function handle(QuestionnaireTemplate $source): QuestionnaireTemplate
    {
        $tenant = Tenant::current();

        if (! $tenant instanceof Tenant) {
            throw new RuntimeException('Cannot copy a template without an active tenant.');
        }

        return DB::transaction(function () use ($source, $tenant): QuestionnaireTemplate {
            $source->loadMissing('items');

            $copy = QuestionnaireTemplate::query()->create([
                'tenant_id' => $tenant->getKey(),
                'name' => $source->name,
                'description' => $source->description,
                'category' => $source->category,
                'source_template_id' => $source->getKey(),
            ]);

            foreach ($source->items as $item) {
                QuestionnaireTemplateItem::query()->create([
                    'questionnaire_template_id' => $copy->getKey(),
                    'type' => $item->type,
                    'title' => $item->title,
                    'instructions' => $item->instructions,
                    'sort_order' => $item->sort_order,
                ]);
            }

            return $copy->load('items');
        });
    }
}
