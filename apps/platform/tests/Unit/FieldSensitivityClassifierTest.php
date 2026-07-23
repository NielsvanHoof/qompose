<?php

declare(strict_types=1);

use App\Enums\ExtractionFieldSensitivity;
use App\Services\Ocr\Normalization\FieldSensitivityClassifier;
use Tests\TestCase;

uses(TestCase::class);

test('field sensitivity classifier tags dutch pii labels', function () {
    $payload = app(FieldSensitivityClassifier::class)->classify([
        'document_type' => 'identity',
        'summary' => null,
        'fields' => [
            ['label' => 'BSN', 'value' => '123456789', 'confidence' => null, 'sensitivity' => null],
            ['label' => 'IBAN', 'value' => 'NL91ABNA0417164300', 'confidence' => null, 'sensitivity' => null],
            ['label' => 'Geboortedatum', 'value' => '01-01-1990', 'confidence' => null, 'sensitivity' => null],
            ['label' => 'Period', 'value' => 'January', 'confidence' => null, 'sensitivity' => null],
        ],
        'tables' => [],
        'notes' => [],
        'confidence' => null,
    ]);

    expect($payload['fields'][0]['sensitivity'])->toBe(ExtractionFieldSensitivity::Bsn->value)
        ->and($payload['fields'][1]['sensitivity'])->toBe(ExtractionFieldSensitivity::Iban->value)
        ->and($payload['fields'][2]['sensitivity'])->toBe(ExtractionFieldSensitivity::DateOfBirth->value)
        ->and($payload['fields'][3]['sensitivity'])->toBeNull();
});

test('field sensitivity classifier falls back to iban value shape', function () {
    $payload = app(FieldSensitivityClassifier::class)->classify([
        'document_type' => null,
        'summary' => null,
        'fields' => [
            [
                'label' => 'Account',
                'value' => 'NL91 ABNA 0417 1643 00',
                'confidence' => null,
                'sensitivity' => null,
            ],
        ],
        'tables' => [],
        'notes' => [],
        'confidence' => null,
    ]);

    expect($payload['fields'][0]['sensitivity'])->toBe(ExtractionFieldSensitivity::Iban->value);
});
