<?php

declare(strict_types=1);

use App\Contracts\Ocr\StartsDocumentOcr;
use App\Services\Ocr\Drivers\MockOcrExtractor;
use App\Services\Ocr\Drivers\TextractDocumentOcr;

test('configured OCR drivers resolve through the factory', function (string $driver, string $expectedClass) {
    config(['ocr.driver' => $driver]);

    expect(app(StartsDocumentOcr::class))->toBeInstanceOf($expectedClass);
})->with([
    'mock' => ['mock', MockOcrExtractor::class],
    'textract' => ['textract', TextractDocumentOcr::class],
]);

test('unsupported OCR drivers fail closed', function () {
    config(['ocr.driver' => 'unsupported']);

    expect(fn (): StartsDocumentOcr => app(StartsDocumentOcr::class))
        ->toThrow(InvalidArgumentException::class, 'Unsupported OCR driver [unsupported].');
});

test('mock OCR cannot be resolved in production', function () {
    $originalEnvironment = $this->app->environment();
    $this->app->instance('env', 'production');
    config(['ocr.driver' => 'mock']);

    try {
        expect(fn (): StartsDocumentOcr => app(StartsDocumentOcr::class))
            ->toThrow(LogicException::class, 'Mock OCR driver cannot be used in production.');
    } finally {
        $this->app->instance('env', $originalEnvironment);
    }
});
