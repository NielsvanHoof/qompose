<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QuestionnaireItemType;
use App\Enums\QuestionnaireTemplateCategory;
use App\Models\QuestionnaireTemplate;
use App\Models\QuestionnaireTemplateItem;
use Illuminate\Database\Seeder;

final class SystemQuestionnaireTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $templateData) {
            $items = $templateData['items'];
            unset($templateData['items']);

            $template = QuestionnaireTemplate::query()->updateOrCreate(
                [
                    'tenant_id' => null,
                    'name' => $templateData['name'],
                    'category' => $templateData['category'],
                ],
                [
                    'description' => $templateData['description'],
                ],
            );

            // Rebuild items so seeder re-runs stay idempotent.
            $template->items()->delete();

            foreach ($items as $index => $item) {
                QuestionnaireTemplateItem::query()->create([
                    'questionnaire_template_id' => $template->id,
                    'type' => $item['type'],
                    'title' => $item['title'],
                    'instructions' => $item['instructions'],
                    'sort_order' => $index,
                ]);
            }
        }
    }

    /**
     * @return list<array{
     *     name: string,
     *     description: string,
     *     category: QuestionnaireTemplateCategory,
     *     items: list<array{type: QuestionnaireItemType, title: string, instructions: string|null}>
     * }>
     */
    private function templates(): array
    {
        return [
            [
                'name' => 'KYC onboarding',
                'description' => 'Standaard klantacceptatie: identificatie, UBO en adresgegevens.',
                'category' => QuestionnaireTemplateCategory::Kyc,
                'items' => [
                    [
                        'type' => QuestionnaireItemType::File,
                        'title' => 'Kopie identiteitsbewijs',
                        'instructions' => 'Upload een geldig paspoort of ID-kaart (voor- en achterkant).',
                    ],
                    [
                        'type' => QuestionnaireItemType::Boolean,
                        'title' => 'UBO-verklaring bevestigd',
                        'instructions' => 'Bevestig dat de opgegeven UBO-informatie volledig en actueel is.',
                    ],
                    [
                        'type' => QuestionnaireItemType::Text,
                        'title' => 'Woon- of vestigingsadres',
                        'instructions' => 'Vul het volledige adres in inclusief postcode en plaats.',
                    ],
                ],
            ],
            [
                'name' => 'Jaarrekening samenstel',
                'description' => 'Basisuitvraag voor het samenstellen van de jaarrekening.',
                'category' => QuestionnaireTemplateCategory::Jaarrekening,
                'items' => [
                    [
                        'type' => QuestionnaireItemType::File,
                        'title' => 'Grootboek / proefbalans',
                        'instructions' => 'Upload de eindbalans of grootboekexport over het boekjaar.',
                    ],
                    [
                        'type' => QuestionnaireItemType::File,
                        'title' => 'Bankafschriften jaareinde',
                        'instructions' => 'Upload de bankafschriften per 31 december.',
                    ],
                    [
                        'type' => QuestionnaireItemType::Boolean,
                        'title' => 'Alle mutaties verwerkt',
                        'instructions' => 'Bevestig dat alle bekende mutaties in de administratie zijn verwerkt.',
                    ],
                    [
                        'type' => QuestionnaireItemType::Text,
                        'title' => 'Bijzonderheden boekjaar',
                        'instructions' => 'Beschrijf relevante gebeurtenissen (overnames, geschillen, subsidies).',
                    ],
                ],
            ],
            [
                'name' => 'Fiscale aangifte',
                'description' => 'Template voor inkomsten- of vennootschapsbelastingaangifte.',
                'category' => QuestionnaireTemplateCategory::Fiscale,
                'items' => [
                    [
                        'type' => QuestionnaireItemType::File,
                        'title' => 'Jaaropgaven / loonstaten',
                        'instructions' => 'Upload alle relevante jaaropgaven van het belastingtijdvak.',
                    ],
                    [
                        'type' => QuestionnaireItemType::File,
                        'title' => 'Aftrekposten onderbouwing',
                        'instructions' => 'Upload bewijsstukken voor geclaimde aftrekposten.',
                    ],
                    [
                        'type' => QuestionnaireItemType::Boolean,
                        'title' => 'Buitenlandse inkomsten',
                        'instructions' => 'Heeft u inkomsten of vermogen in het buitenland?',
                    ],
                    [
                        'type' => QuestionnaireItemType::Text,
                        'title' => 'Toelichting bijzondere situaties',
                        'instructions' => 'Licht eventuele bijzondere fiscale situaties toe.',
                    ],
                ],
            ],
            [
                'name' => 'PBC controlelijst',
                'description' => 'Prepared-by-client lijst voor jaarrekeningcontroles.',
                'category' => QuestionnaireTemplateCategory::Pbc,
                'items' => [
                    [
                        'type' => QuestionnaireItemType::File,
                        'title' => 'Debiteurenlijst',
                        'instructions' => 'Upload de openstaande debiteuren per balansdatum.',
                    ],
                    [
                        'type' => QuestionnaireItemType::File,
                        'title' => 'Crediteurenlijst',
                        'instructions' => 'Upload de openstaande crediteuren per balansdatum.',
                    ],
                    [
                        'type' => QuestionnaireItemType::File,
                        'title' => 'Voorraadlijst',
                        'instructions' => 'Upload de voorraadopname indien van toepassing.',
                    ],
                    [
                        'type' => QuestionnaireItemType::Boolean,
                        'title' => 'Volledigheid PBC-stukken',
                        'instructions' => 'Bevestig dat alle gevraagde PBC-stukken zijn aangeleverd.',
                    ],
                ],
            ],
        ];
    }
}
