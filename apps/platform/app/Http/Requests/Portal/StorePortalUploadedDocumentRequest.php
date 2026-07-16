<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\Enums\DocumentRequestStatus;
use App\Enums\DossierStatus;
use App\Enums\QuestionnaireItemType;
use App\Http\Middleware\ResolveClientPortalGrant;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

final class StorePortalUploadedDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $grant = $this->attributes->get(ResolveClientPortalGrant::REQUEST_ATTRIBUTE);
        $documentRequestId = $this->route('documentRequest');

        if (! $grant instanceof ClientAccessGrant || ! is_numeric($documentRequestId)) {
            return false;
        }

        $documentRequest = DocumentRequest::query()->find((int) $documentRequestId);

        // Let the controller return its existing concealed 404 for missing or cross-dossier IDs.
        if (! $documentRequest instanceof DocumentRequest
            || $documentRequest->dossier_id !== $grant->dossier_id) {
            return true;
        }

        $dossier = $documentRequest->dossier;

        return $dossier instanceof Dossier
            && $documentRequest->type === QuestionnaireItemType::File
            && $dossier->status !== DossierStatus::Completed
            && in_array($documentRequest->status, [
                DocumentRequestStatus::Pending,
                DocumentRequestStatus::Rejected,
            ], true);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'document' => [
                'required',
                File::types(['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx'])
                    ->max('20mb'),
            ],
        ];
    }
}
