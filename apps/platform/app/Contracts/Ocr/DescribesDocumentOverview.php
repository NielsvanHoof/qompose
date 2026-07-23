<?php

declare(strict_types=1);

namespace App\Contracts\Ocr;

/**
 * Fills overview metadata (type / summary / notes) for a Textract-mapped extraction.
 *
 * @phpstan-import-type DocumentExtractionPayload from DocumentExtraction
 * @phpstan-import-type DocumentOverview from DocumentExtraction
 */
interface DescribesDocumentOverview
{
    /**
     * @param  DocumentExtractionPayload  $payload
     * @return DocumentOverview
     */
    public function describe(array $payload): array;
}
