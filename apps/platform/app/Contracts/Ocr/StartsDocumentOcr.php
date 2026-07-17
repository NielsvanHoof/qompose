<?php

declare(strict_types=1);

namespace App\Contracts\Ocr;

use App\Models\UploadedDocument;

/**
 * Starts OCR for an uploaded document that is already claimed as processing.
 * Mock drivers complete synchronously; Textract leaves the row in processing.
 */
interface StartsDocumentOcr
{
    public function start(UploadedDocument $document): void;
}
