<?php

declare(strict_types=1);

namespace App\Services\Ocr\Drivers;

use App\Contracts\Ocr\StartsDocumentOcr;
use App\Enums\OcrDriver;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use LogicException;

final class OcrDriverFactory
{
    public function __construct(
        private readonly Application $application,
        private readonly MockOcrExtractor $mockOcrExtractor,
        private readonly TextractDocumentOcr $textractDocumentOcr,
    ) {}

    public function make(string $driver): StartsDocumentOcr
    {
        $ocrDriver = OcrDriver::tryFrom($driver);

        if (! $ocrDriver instanceof OcrDriver) {
            throw new InvalidArgumentException("Unsupported OCR driver [{$driver}].");
        }

        if ($ocrDriver === OcrDriver::Mock && $this->application->isProduction()) {
            throw new LogicException('Mock OCR driver cannot be used in production.');
        }

        return match ($ocrDriver) {
            OcrDriver::Mock => $this->mockOcrExtractor,
            OcrDriver::Textract => $this->textractDocumentOcr,
        };
    }
}
