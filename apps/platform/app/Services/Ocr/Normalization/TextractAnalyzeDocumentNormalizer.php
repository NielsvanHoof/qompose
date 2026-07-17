<?php

declare(strict_types=1);

namespace App\Services\Ocr\Normalization;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

/**
 * Turns Textract AnalyzeDocument Blocks into a compact JSON-friendly payload.
 * Focuses on FORMS (key/value) and TABLES — not raw geometry.
 */
final class TextractAnalyzeDocumentNormalizer
{
    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return array{
     *     key_values: array<string, string|list<string>>,
     *     tables: list<list<list<string>>>
     * }
     */
    public function normalize(array $blocks): array
    {
        $byId = [];

        foreach ($blocks as $block) {
            $id = $block['Id'] ?? null;

            if (is_string($id) && $id !== '') {
                $byId[$id] = $block;
            }
        }

        return [
            'key_values' => $this->extractKeyValues($byId),
            'tables' => $this->extractTables($byId),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $byId
     * @return array<string, string|list<string>>
     */
    private function extractKeyValues(array $byId): array
    {
        $keyValues = [];

        foreach ($byId as $block) {
            if (($block['BlockType'] ?? null) !== 'KEY_VALUE_SET') {
                continue;
            }

            $entityTypes = $block['EntityTypes'] ?? [];

            if (! is_array($entityTypes) || ! in_array('KEY', $entityTypes, true)) {
                continue;
            }

            $keyText = $this->resolveTextFromRelationships($block, $byId, 'CHILD');
            $valueIds = $this->relationshipIds($block, 'VALUE');
            $valueText = '';

            foreach ($valueIds as $valueId) {
                $valueBlock = $byId[$valueId] ?? null;

                if (! is_array($valueBlock)) {
                    continue;
                }

                $piece = $this->resolveTextFromRelationships($valueBlock, $byId, 'CHILD');

                if ($piece !== '') {
                    $valueText = $valueText === '' ? $piece : mb_trim($valueText.' '.$piece);
                }
            }

            $key = $this->normalizeLabel($keyText);

            if ($key === '') {
                continue;
            }

            // Duplicate keys (common on payslips) become a list.
            if (! array_key_exists($key, $keyValues)) {
                $keyValues[$key] = $valueText;

                continue;
            }

            $existing = $keyValues[$key];

            if (is_string($existing)) {
                $keyValues[$key] = [$existing, $valueText];

                continue;
            }

            // Append onto the existing list (avoid [] on a string|list union).
            $existing[] = $valueText;
            $keyValues[$key] = $existing;
        }

        return $keyValues;
    }

    /**
     * @param  array<string, array<string, mixed>>  $byId
     * @return list<list<list<string>>>
     */
    private function extractTables(array $byId): array
    {
        $tables = [];

        foreach ($byId as $block) {
            if (($block['BlockType'] ?? null) !== 'TABLE') {
                continue;
            }

            $cells = [];

            foreach ($this->relationshipIds($block, 'CHILD') as $childId) {
                $cell = $byId[$childId] ?? null;

                if (! is_array($cell) || ($cell['BlockType'] ?? null) !== 'CELL') {
                    continue;
                }

                $rowIndex = $cell['RowIndex'] ?? null;
                $columnIndex = $cell['ColumnIndex'] ?? null;

                if (! is_int($rowIndex) || ! is_int($columnIndex)) {
                    continue;
                }

                $cells[] = [
                    'row' => $rowIndex,
                    'column' => $columnIndex,
                    'text' => $this->resolveTextFromRelationships($cell, $byId, 'CHILD'),
                ];
            }

            if ($cells === []) {
                continue;
            }

            $maxRow = max(array_column($cells, 'row'));
            $maxColumn = max(array_column($cells, 'column'));
            $sparse = [];

            for ($row = 1; $row <= $maxRow; $row++) {
                for ($column = 1; $column <= $maxColumn; $column++) {
                    $sparse[$row][$column] = '';
                }
            }

            foreach ($cells as $cell) {
                $sparse[$cell['row']][$cell['column']] = $cell['text'];
            }

            // Rebuild as contiguous lists — sparse int-key writes lose list types for PHPStan.
            $grid = [];

            for ($row = 1; $row <= $maxRow; $row++) {
                $rowCells = [];

                for ($column = 1; $column <= $maxColumn; $column++) {
                    $rowCells[] = $sparse[$row][$column];
                }

                $grid[] = $rowCells;
            }

            $tables[] = $grid;
        }

        return $tables;
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  array<string, array<string, mixed>>  $byId
     */
    private function resolveTextFromRelationships(array $block, array $byId, string $type): string
    {
        $parts = [];

        foreach ($this->relationshipIds($block, $type) as $childId) {
            $child = $byId[$childId] ?? null;

            if (! is_array($child)) {
                continue;
            }

            $blockType = $child['BlockType'] ?? null;

            if ($blockType === 'WORD' || $blockType === 'SELECTION_ELEMENT') {
                $text = $child['Text'] ?? null;

                if ($blockType === 'SELECTION_ELEMENT') {
                    $status = $child['SelectionStatus'] ?? null;
                    $parts[] = $status === 'SELECTED' ? 'X' : '';

                    continue;
                }

                if (is_string($text) && $text !== '') {
                    $parts[] = $text;
                }
            }
        }

        return $this->normalizeLabel(implode(' ', $parts));
    }

    /**
     * @param  array<string, mixed>  $block
     * @return list<string>
     */
    private function relationshipIds(array $block, string $type): array
    {
        $relationships = $block['Relationships'] ?? [];

        if (! is_array($relationships)) {
            return [];
        }

        foreach ($relationships as $relationship) {
            if (! is_array($relationship)) {
                continue;
            }

            if (($relationship['Type'] ?? null) !== $type) {
                continue;
            }

            $ids = $relationship['Ids'] ?? [];

            if (! is_array($ids)) {
                return [];
            }

            $stringIds = [];

            foreach ($ids as $id) {
                if (is_string($id) && $id !== '') {
                    $stringIds[] = $id;
                }
            }

            return $stringIds;
        }

        return [];
    }

    private function normalizeLabel(string $text): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', mb_trim($text));

        return is_string($collapsed) ? $collapsed : mb_trim($text);
    }
}
