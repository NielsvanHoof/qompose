<?php

declare(strict_types=1);

namespace App\Services\Ocr\Normalization;

use App\Contracts\Ocr\DocumentExtraction;

use function is_array;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;

/**
 * Maps Textract AnalyzeDocument FORMS + TABLES blocks into our extraction schema.
 * Field confidence comes from VALUE blocks; document confidence averages those scores.
 *
 * @phpstan-import-type DocumentExtractionField from DocumentExtraction
 * @phpstan-import-type DocumentExtractionTable from DocumentExtraction
 * @phpstan-import-type DocumentExtractionPayload from DocumentExtraction
 */
final class TextractFormsTablesMapper
{
    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return DocumentExtractionPayload
     */
    public function map(array $blocks): array
    {
        $byId = [];

        foreach ($blocks as $block) {
            $id = $block['Id'] ?? null;

            if (is_string($id) && $id !== '') {
                $byId[$id] = $block;
            }
        }

        $fields = $this->mapFields($blocks, $byId);
        $tables = $this->mapTables($blocks, $byId);
        $confidenceScores = [];

        foreach ($fields as $field) {
            if ($field['confidence'] !== null) {
                $confidenceScores[] = $field['confidence'];
            }
        }

        return [
            'document_type' => null,
            'summary' => null,
            'fields' => $fields,
            'tables' => $tables,
            'notes' => [],
            'confidence' => $confidenceScores === []
                ? null
                : round(array_sum($confidenceScores) / count($confidenceScores), 4),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @param  array<string, array<string, mixed>>  $byId
     * @return list<DocumentExtractionField>
     */
    private function mapFields(array $blocks, array $byId): array
    {
        $fields = [];

        foreach ($blocks as $block) {
            if (($block['BlockType'] ?? null) !== 'KEY_VALUE_SET') {
                continue;
            }

            $entityTypes = $block['EntityTypes'] ?? [];

            if (! is_array($entityTypes) || ! in_array('KEY', $entityTypes, true)) {
                continue;
            }

            $label = $this->resolveChildText($block, $byId, 'CHILD');
            $valueBlock = $this->resolveRelatedBlock($block, $byId, 'VALUE');
            $value = $valueBlock === null
                ? ''
                : $this->resolveChildText($valueBlock, $byId, 'CHILD');

            $label = mb_trim($label);
            $value = mb_trim($value);

            if ($label === '' && $value === '') {
                continue;
            }

            if ($label === '') {
                $label = 'Untitled';
            }

            $fields[] = [
                'label' => $label,
                'value' => $value,
                'confidence' => $valueBlock === null
                    ? null
                    : $this->normalizeConfidence($valueBlock['Confidence'] ?? null),
                'sensitivity' => null,
            ];
        }

        return $fields;
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @param  array<string, array<string, mixed>>  $byId
     * @return list<DocumentExtractionTable>
     */
    private function mapTables(array $blocks, array $byId): array
    {
        $tables = [];

        foreach ($blocks as $block) {
            if (($block['BlockType'] ?? null) !== 'TABLE') {
                continue;
            }

            $cellsByPosition = [];
            $maxRow = 0;
            $maxCol = 0;
            $hasHeaderRow = false;

            foreach ($this->relationshipIds($block, 'CHILD') as $childId) {
                $cell = $byId[$childId] ?? null;

                if ($cell === null || ($cell['BlockType'] ?? null) !== 'CELL') {
                    continue;
                }

                $rowIndex = $cell['RowIndex'] ?? null;
                $colIndex = $cell['ColumnIndex'] ?? null;

                if (! is_int($rowIndex) || ! is_int($colIndex) || $rowIndex < 1 || $colIndex < 1) {
                    continue;
                }

                $maxRow = max($maxRow, $rowIndex);
                $maxCol = max($maxCol, $colIndex);

                $entityTypes = $cell['EntityTypes'] ?? [];

                if (is_array($entityTypes) && in_array('COLUMN_HEADER', $entityTypes, true)) {
                    $hasHeaderRow = true;
                }

                $cellsByPosition[$rowIndex][$colIndex] = mb_trim(
                    $this->resolveChildText($cell, $byId, 'CHILD'),
                );
            }

            if ($maxRow === 0 || $maxCol === 0) {
                continue;
            }

            $headers = [];
            $rows = [];
            $dataStartRow = 1;

            if ($hasHeaderRow) {
                for ($col = 1; $col <= $maxCol; $col++) {
                    $headers[] = $cellsByPosition[1][$col] ?? '';
                }
                $dataStartRow = 2;
            }

            for ($row = $dataStartRow; $row <= $maxRow; $row++) {
                $rowCells = [];

                for ($col = 1; $col <= $maxCol; $col++) {
                    $rowCells[] = $cellsByPosition[$row][$col] ?? '';
                }

                // Skip fully empty rows.
                if (implode('', $rowCells) === '') {
                    continue;
                }

                $rows[] = $rowCells;
            }

            if ($headers === [] && $rows === []) {
                continue;
            }

            $tables[] = [
                'title' => null,
                'headers' => $headers,
                'rows' => $rows,
            ];
        }

        return $tables;
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  array<string, array<string, mixed>>  $byId
     * @return array<string, mixed>|null
     */
    private function resolveRelatedBlock(
        array $block,
        array $byId,
        string $relationshipType,
    ): ?array {
        foreach ($this->relationshipIds($block, $relationshipType) as $id) {
            $related = $byId[$id] ?? null;

            if ($related !== null) {
                return $related;
            }
        }

        return null;
    }

    /**
     * Collect WORD / SELECTION_ELEMENT text under CHILD relationships.
     *
     * @param  array<string, mixed>  $block
     * @param  array<string, array<string, mixed>>  $byId
     */
    private function resolveChildText(
        array $block,
        array $byId,
        string $relationshipType,
    ): string {
        $parts = [];

        foreach ($this->relationshipIds($block, $relationshipType) as $childId) {
            $child = $byId[$childId] ?? null;

            if ($child === null) {
                continue;
            }

            $blockType = $child['BlockType'] ?? null;

            if ($blockType === 'WORD') {
                $text = $child['Text'] ?? null;

                if (is_string($text) && $text !== '') {
                    $parts[] = $text;
                }

                continue;
            }

            if ($blockType === 'SELECTION_ELEMENT') {
                $status = $child['SelectionStatus'] ?? null;
                $parts[] = $status === 'SELECTED' ? 'Yes' : 'No';
            }
        }

        return implode(' ', $parts);
    }

    /**
     * @param  array<string, mixed>  $block
     * @return list<string>
     */
    private function relationshipIds(array $block, string $type): array
    {
        $relationships = $block['Relationships'] ?? null;

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

            $ids = $relationship['Ids'] ?? null;

            if (! is_array($ids)) {
                return [];
            }

            $normalized = [];

            foreach ($ids as $id) {
                if (is_string($id) && $id !== '') {
                    $normalized[] = $id;
                }
            }

            return $normalized;
        }

        return [];
    }

    private function normalizeConfidence(mixed $confidence): ?float
    {
        if (is_int($confidence) || is_float($confidence)) {
            $value = (float) $confidence;
        } elseif (is_string($confidence) && is_numeric($confidence)) {
            $value = (float) $confidence;
        } else {
            return null;
        }

        if ($value < 0.0) {
            return null;
        }

        if ($value > 1.0) {
            $value /= 100.0;
        }

        return round(min(1.0, $value), 4);
    }
}
