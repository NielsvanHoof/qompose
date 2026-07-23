import EmptyState from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import type {
    DocumentExtraction,
    DocumentExtractionField,
    DocumentExtractionTable,
} from '@/features/document-requests/types';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Renders Bedrock-structured OCR fields, tables, and notes, with raw JSON collapsed.
 */
export default function DocumentExtractionView({
    extraction,
    rawJson,
}: {
    extraction: DocumentExtraction | null;
    rawJson: string | null;
}) {
    const { t } = useTranslation();

    if (extraction === null && (rawJson === null || rawJson === '')) {
        return (
            <EmptyState
                title={t('No extraction data is available for this file yet.')}
            />
        );
    }

    const fields = extraction?.fields ?? [];
    const tables = extraction?.tables ?? [];
    const notes = extraction?.notes ?? [];
    const documentType = extraction?.document_type ?? null;
    const summary = extraction?.summary ?? null;
    // Build stable keys once so list keys are not bare map indexes.
    const keyedFields = keyedFieldsList(fields);
    const keyedTables = keyedTablesList(tables);
    const keyedNotes = keyedStrings(notes);

    return (
        <div className="space-y-8">
            {(documentType !== null || summary !== null) && (
                <section className="space-y-2">
                    <h2 className="text-lg font-semibold tracking-tight">
                        {t('Overview')}
                    </h2>
                    {documentType !== null && (
                        <p className="text-sm text-muted-foreground">
                            {/* Document type is OCR content — do not translate. */}
                            <span className="font-medium text-foreground">
                                {t('Type')}:
                            </span>{' '}
                            {documentType}
                        </p>
                    )}
                    {summary !== null && (
                        <p className="text-sm whitespace-pre-wrap">{summary}</p>
                    )}
                </section>
            )}

            <section className="space-y-3">
                <h2 className="text-lg font-semibold tracking-tight">
                    {t('Fields')}
                </h2>
                {keyedFields.length === 0 ? (
                    <EmptyState title={t('No form fields detected.')} />
                ) : (
                    <dl className="divide-y rounded-md border">
                        {keyedFields.map(({ field, key }) => (
                            <div
                                key={key}
                                className="grid gap-1 px-3 py-2 sm:grid-cols-[minmax(8rem,14rem)_1fr] sm:gap-4"
                            >
                                {/* OCR field labels are document content — do not translate. */}
                                <dt className="text-sm font-medium text-muted-foreground">
                                    {field.label}
                                </dt>
                                <dd className="text-sm whitespace-pre-wrap">
                                    {formatFieldValue(field.value)}
                                </dd>
                            </div>
                        ))}
                    </dl>
                )}
            </section>

            <section className="space-y-3">
                <h2 className="text-lg font-semibold tracking-tight">
                    {t('Tables')}
                </h2>
                {keyedTables.length === 0 ? (
                    <EmptyState title={t('No tables detected.')} />
                ) : (
                    <div className="space-y-4">
                        {keyedTables.map(({ table, key: tableId }) => {
                            const headers = keyedStrings(table.headers);
                            const rows = keyedRows(table.rows);

                            return (
                                <div key={tableId} className="space-y-2">
                                    {table.title !== null &&
                                        table.title !== '' && (
                                            <h3 className="text-sm font-medium">
                                                {table.title}
                                            </h3>
                                        )}
                                    <div className="overflow-x-auto rounded-md border">
                                        <table className="w-full min-w-md border-collapse text-left text-sm">
                                            {headers.length > 0 && (
                                                <thead>
                                                    <tr className="border-b bg-muted/40">
                                                        {headers.map(
                                                            ({
                                                                value: header,
                                                                key,
                                                            }) => (
                                                                <th
                                                                    key={`${tableId}|h|${key}`}
                                                                    className="px-3 py-2 font-medium"
                                                                >
                                                                    {header}
                                                                </th>
                                                            ),
                                                        )}
                                                    </tr>
                                                </thead>
                                            )}
                                            <tbody>
                                                {rows.map(
                                                    ({ row, key: rowKey }) => {
                                                        const rowId = `${tableId}|r|${rowKey}`;
                                                        const cells =
                                                            keyedCells(row);

                                                        return (
                                                            <tr
                                                                key={rowId}
                                                                className="border-b last:border-b-0"
                                                            >
                                                                {cells.map(
                                                                    ({
                                                                        cell,
                                                                        key,
                                                                    }) => (
                                                                        <td
                                                                            key={`${rowId}|${key}`}
                                                                            className="px-3 py-2 align-top whitespace-pre-wrap"
                                                                        >
                                                                            {
                                                                                cell
                                                                            }
                                                                        </td>
                                                                    ),
                                                                )}
                                                            </tr>
                                                        );
                                                    },
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </section>

            {keyedNotes.length > 0 && (
                <section className="space-y-3">
                    <h2 className="text-lg font-semibold tracking-tight">
                        {t('Notes')}
                    </h2>
                    <ul className="list-disc space-y-1 pl-5 text-sm">
                        {keyedNotes.map(({ value: note, key }) => (
                            <li key={`note:${key}`}>{note}</li>
                        ))}
                    </ul>
                </section>
            )}

            {rawJson !== null && rawJson !== '' && (
                <Collapsible className="space-y-2">
                    <CollapsibleTrigger asChild>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="px-0"
                        >
                            {t('Raw JSON')}
                        </Button>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <pre className="max-h-96 overflow-auto rounded-md border bg-muted/40 px-3 py-2 font-mono text-xs whitespace-pre-wrap">
                            {formatRawJson(rawJson)}
                        </pre>
                    </CollapsibleContent>
                </Collapsible>
            )}
        </div>
    );
}

function formatFieldValue(value: string | string[]): string {
    return Array.isArray(value) ? value.join(', ') : value;
}

function formatRawJson(raw: string): string {
    try {
        return JSON.stringify(JSON.parse(raw), null, 2);
    } catch {
        return raw;
    }
}

/** Assigns stable keys within a row when duplicate cell text appears. */
function keyedCells(row: string[]): Array<{ cell: string; key: string }> {
    return keyedStrings(row).map(({ value, key }) => ({ cell: value, key }));
}

/** Occurrence-based keys for plain strings (headers, notes, cells). */
function keyedStrings(values: string[]): Array<{ value: string; key: string }> {
    const seen = new Map<string, number>();

    return values.map((value) => {
        const occurrence = (seen.get(value) ?? 0) + 1;
        seen.set(value, occurrence);

        return { value, key: `${value}#${occurrence}` };
    });
}

/** Stable keys for field rows (label + serialized value). */
function keyedFieldsList(
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
function keyedTablesList(
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
function keyedRows(rows: string[][]): Array<{ row: string[]; key: string }> {
    const seen = new Map<string, number>();

    return rows.map((row) => {
        const fingerprint = JSON.stringify(row);
        const occurrence = (seen.get(fingerprint) ?? 0) + 1;
        seen.set(fingerprint, occurrence);

        return { row, key: `${fingerprint}#${occurrence}` };
    });
}
