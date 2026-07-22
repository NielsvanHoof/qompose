<?php

declare(strict_types=1);

namespace App\Http\Requests\Dossiers;

use App\Http\Requests\Concerns\LocalizesValidationAttributes;
use App\Models\DocumentRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

final class StoreUploadedDocumentRequest extends FormRequest
{
    use LocalizesValidationAttributes;

    /**
     * Staff who can create dossiers may upload files onto document requests.
     */
    public function authorize(): bool
    {
        $documentRequest = $this->route('documentRequest');

        if (! $documentRequest instanceof DocumentRequest) {
            return false;
        }

        return $this->user()?->can('upload', $documentRequest) ?? false;
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
