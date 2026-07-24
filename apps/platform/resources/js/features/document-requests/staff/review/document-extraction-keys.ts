import { formatFieldValue } from '@/features/document-requests/staff/review/document-extraction-helpers';
import type {
    DocumentExtractionField,
    DocumentExtractionTable,
} from '@/features/document-requests/types';

/** Assigns stable keys within a row when duplicate cell text appears. */
export function keyedCells(
    row: string[],
): Array<{ cell: string; key: string }> {
    return keyedStrings(row).map(({ value, key }) => ({ cell: value, key }));
}

/** Occurrence-based keys for plain strings (headers, notes, cells). */
export function keyedStrings(
    values: string[],
): Array<{ value: string; key: string }> {
    const seen = new Map<string, number>();

    return values.map((value) => {
        const occurrence = (seen.get(value) ?? 0) + 1;
        seen.set(value, occurrence);

        return { value, key: `${value}#${occurrence}` };
    });
}

/** Stable keys for field rows (label + serialized value). */
export function keyedFieldsList(
    fields: DocumentExtractionField[],
): Array<{ field: DocumentExtractionField; key: string }> {
    const seen = new Map<string, number>();

    return fields.map((field) => {
        const fingerprint = `${field.label}:${formatFieldValue(field.value)}`;
        const occurrence = (seen.get(fingerprint) ?? 0) + 1;
        seen.set(fingerprint, occurrence);

        return { field, key: `${fingerprint}#${occurrence}` };
    });
}

/** Stable keys for tables from title + shape fingerprint. */
export function keyedTablesList(
    tables: DocumentExtractionTable[],
): Array<{ table: DocumentExtractionTable; key: string }> {
    const seen = new Map<string, number>();

    return tables.map((table) => {
        const fingerprint = JSON.stringify(table);
        const occurrence = (seen.get(fingerprint) ?? 0) + 1;
        seen.set(fingerprint, occurrence);

        return { table, key: `table:${fingerprint}#${occurrence}` };
    });
}

/** Stable keys for table rows from cell content. */
export function keyedRows(
    rows: string[][],
): Array<{ row: string[]; key: string }> {
    const seen = new Map<string, number>();

    return rows.map((row) => {
        const fingerprint = JSON.stringify(row);
        const occurrence = (seen.get(fingerprint) ?? 0) + 1;
        seen.set(fingerprint, occurrence);

        return { row, key: `${fingerprint}#${occurrence}` };
    });
}
