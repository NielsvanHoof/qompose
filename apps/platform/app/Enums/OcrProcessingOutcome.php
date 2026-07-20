<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Whether OCR completion happens inside start() or arrives later (e.g. Textract via SQS).
 */
enum OcrProcessingOutcome: string
{
    case Immediate = 'immediate';
    case Deferred = 'deferred';
}
