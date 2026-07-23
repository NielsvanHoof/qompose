<?php

declare(strict_types=1);

use App\Services\Ocr\Normalization\TextractLineTextExtractor;
use Tests\TestCase;

uses(TestCase::class);

test('textract line text extractor joins lines by page and ignores words', function () {
    $text = app(TextractLineTextExtractor::class)->extract([
        ['Id' => 'w1', 'BlockType' => 'WORD', 'Page' => 1, 'Text' => 'skip'],
        ['Id' => 'l1', 'BlockType' => 'LINE', 'Page' => 2, 'Text' => 'Page two line'],
        ['Id' => 'l2', 'BlockType' => 'LINE', 'Page' => 1, 'Text' => 'BSN 123'],
        ['Id' => 'l3', 'BlockType' => 'LINE', 'Page' => 1, 'Text' => 'Name Ada'],
    ]);

    expect($text)->toBe(
        "--- Page 1 ---\nBSN 123\nName Ada\n\n--- Page 2 ---\nPage two line"
    );
});

test('textract line text extractor returns empty string when no lines exist', function () {
    $text = app(TextractLineTextExtractor::class)->extract([
        ['Id' => 'w1', 'BlockType' => 'WORD', 'Text' => 'only-word'],
    ]);

    expect($text)->toBe('');
});
