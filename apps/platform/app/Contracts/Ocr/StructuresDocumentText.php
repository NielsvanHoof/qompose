<?php

declare(strict_types=1);

namespace App\Contracts\Ocr;

/**
 * Turns plain OCR line text into the structured extraction JSON payload.
 *
 * @phpstan-type DocumentExtractionField array{label: string, value: string|list<string>}
 * @phpstan-type DocumentExtractionTable array{title: ?string, headers: list<string>, rows: list<list<string>>}
 * @phpstan-type DocumentExtractionPayload array{
 *     document_type: ?string,
 *     summary: ?string,
 *     fields: list<DocumentExtractionField>,
 *     tables: list<DocumentExtractionTable>,
 *     notes: list<string>
 * }
 */
interface StructuresDocumentText
{
    /**
     * @return DocumentExtractionPayload
     */
    public function structure(string $plainText): array;
}
