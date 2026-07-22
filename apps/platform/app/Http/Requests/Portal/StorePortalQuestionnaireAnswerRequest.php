<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\Enums\QuestionnaireItemType;
use App\Enums\SubmissionContext;
use App\Http\Middleware\ResolveClientPortalGrant;
use App\Http\Requests\Concerns\LocalizesValidationAttributes;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Transitions\DocumentRequestTransitions;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StorePortalQuestionnaireAnswerRequest extends FormRequest
{
    use LocalizesValidationAttributes;

    public function __construct(
        private readonly DocumentRequestTransitions $documentRequestTransitions,
    ) {
        parent::__construct();
    }

    public function authorize(): bool
    {
        $grant = $this->attributes->get(ResolveClientPortalGrant::REQUEST_ATTRIBUTE);
        $documentRequest = $this->route('documentRequest');
        $dossier = $documentRequest instanceof DocumentRequest
            ? $documentRequest->dossier
            : null;

        return $grant instanceof ClientAccessGrant
            && $documentRequest instanceof DocumentRequest
            && $dossier instanceof Dossier
            && $documentRequest->dossier_id === $grant->dossier_id
            && $documentRequest->type !== QuestionnaireItemType::File
            && $this->documentRequestTransitions->canSubmit(
                $documentRequest,
                SubmissionContext::Portal,
                $dossier,
            );
    }

    /** @return array<string, list<ValidationRule|string>> */
    public function rules(): array
    {
        $documentRequest = $this->route('documentRequest');

        if ($documentRequest instanceof DocumentRequest
            && $documentRequest->type === QuestionnaireItemType::Boolean) {
            return [
                'answer_boolean' => ['required', 'boolean'],
            ];
        }

        return [
            'answer_text' => ['required', 'string', 'max:5000'],
        ];
    }
}
