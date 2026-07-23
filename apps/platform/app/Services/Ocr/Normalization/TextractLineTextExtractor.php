<?php

declare(strict_types=1);

namespace App\Services\Ocr\Normalization;

use function is_int;
use function is_string;

/**
 * Flattens Textract DetectDocumentText LINE blocks into readable plain text.
 * Preserves page breaks so Bedrock can keep multi-page context.
 */
final class TextractLineTextExtractor
{
    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    public function extract(array $blocks): string
    {
        $linesByPage = [];

        foreach ($blocks as $block) {
            if (($block['BlockType'] ?? null) !== 'LINE') {
                continue;
            }

            $text = $block['Text'] ?? null;

            if (! is_string($text) || $text === '') {
                continue;
            }

            // Page is 1-based when present; default to page 1 for single-page jobs.
            $page = $block['Page'] ?? 1;
            $pageNumber = is_int($page) && $page > 0 ? $page : 1;
            $linesByPage[$pageNumber][] = mb_trim($text);
        }

        if ($linesByPage === []) {
            return '';
        }

        ksort($linesByPage);

        $sections = [];

        foreach ($linesByPage as $pageNumber => $lines) {
            $body = implode("\n", $lines);
            $sections[] = "--- Page {$pageNumber} ---\n{$body}";
        }

        return implode("\n\n", $sections);
    }
}
