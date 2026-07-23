<?php

declare(strict_types=1);

namespace App\Contracts\Ocr;

/**
 * Shared extraction JSON shape stored on uploaded_documents.extracted_text.
 *
 * @phpstan-type DocumentExtractionField array{
 *     label: string,
 *     value: string|list<string>,
 *     confidence: float|null,
 *     sensitivity: string|null
 * }
 * @phpstan-type DocumentExtractionTable array{title: ?string, headers: list<string>, rows: list<list<string>>}
 * @phpstan-type DocumentExtractionPayload array{
 *     document_type: ?string,
 *     summary: ?string,
 *     fields: list<DocumentExtractionField>,
 *     tables: list<DocumentExtractionTable>,
 *     notes: list<string>,
 *     confidence: float|null
 * }
 * @phpstan-type DocumentOverview array{
 *     document_type: ?string,
 *     summary: ?string,
 *     notes: list<string>
 * }
 */
interface DocumentExtraction {}
