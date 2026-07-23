<?php

declare(strict_types=1);

use App\Services\Ocr\Normalization\TextractFormsTablesMapper;
use Tests\TestCase;

uses(TestCase::class);

test('textract forms tables mapper maps key value pairs and tables with confidence', function () {
    $payload = app(TextractFormsTablesMapper::class)->map([
        [
            'Id' => 'key-1',
            'BlockType' => 'KEY_VALUE_SET',
            'EntityTypes' => ['KEY'],
            'Relationships' => [
                ['Type' => 'CHILD', 'Ids' => ['word-key']],
                ['Type' => 'VALUE', 'Ids' => ['value-1']],
            ],
        ],
        [
            'Id' => 'value-1',
            'BlockType' => 'KEY_VALUE_SET',
            'EntityTypes' => ['VALUE'],
            'Confidence' => 98.0,
            'Relationships' => [
                ['Type' => 'CHILD', 'Ids' => ['word-value']],
            ],
        ],
        ['Id' => 'word-key', 'BlockType' => 'WORD', 'Text' => 'BSN'],
        ['Id' => 'word-value', 'BlockType' => 'WORD', 'Text' => '123456789'],
        [
            'Id' => 'table-1',
            'BlockType' => 'TABLE',
            'Relationships' => [
                ['Type' => 'CHILD', 'Ids' => ['c11', 'c12', 'c21', 'c22']],
            ],
        ],
        [
            'Id' => 'c11',
            'BlockType' => 'CELL',
            'RowIndex' => 1,
            'ColumnIndex' => 1,
            'EntityTypes' => ['COLUMN_HEADER'],
            'Relationships' => [['Type' => 'CHILD', 'Ids' => ['w-a']]],
        ],
        [
            'Id' => 'c12',
            'BlockType' => 'CELL',
            'RowIndex' => 1,
            'ColumnIndex' => 2,
            'EntityTypes' => ['COLUMN_HEADER'],
            'Relationships' => [['Type' => 'CHILD', 'Ids' => ['w-b']]],
        ],
        [
            'Id' => 'c21',
            'BlockType' => 'CELL',
            'RowIndex' => 2,
            'ColumnIndex' => 1,
            'Relationships' => [['Type' => 'CHILD', 'Ids' => ['w-1']]],
        ],
        [
            'Id' => 'c22',
            'BlockType' => 'CELL',
            'RowIndex' => 2,
            'ColumnIndex' => 2,
            'Relationships' => [['Type' => 'CHILD', 'Ids' => ['w-2']]],
        ],
        ['Id' => 'w-a', 'BlockType' => 'WORD', 'Text' => 'A'],
        ['Id' => 'w-b', 'BlockType' => 'WORD', 'Text' => 'B'],
        ['Id' => 'w-1', 'BlockType' => 'WORD', 'Text' => '1'],
        ['Id' => 'w-2', 'BlockType' => 'WORD', 'Text' => '2'],
    ]);

    expect($payload['fields'])->toBe([
        [
            'label' => 'BSN',
            'value' => '123456789',
            'confidence' => 0.98,
            'sensitivity' => null,
        ],
    ])
        ->and($payload['confidence'])->toBe(0.98)
        ->and($payload['tables'])->toBe([
            [
                'title' => null,
                'headers' => ['A', 'B'],
                'rows' => [['1', '2']],
            ],
        ])
        ->and($payload['document_type'])->toBeNull()
        ->and($payload['summary'])->toBeNull()
        ->and($payload['notes'])->toBe([]);
});

test('textract forms tables mapper returns empty payload when no forms or tables exist', function () {
    $payload = app(TextractFormsTablesMapper::class)->map([
        ['Id' => 'line-1', 'BlockType' => 'LINE', 'Text' => 'orphan'],
    ]);

    expect($payload)->toBe([
        'document_type' => null,
        'summary' => null,
        'fields' => [],
        'tables' => [],
        'notes' => [],
        'confidence' => null,
    ]);
});
