import { Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    ConfidenceBadge,
    formatFieldValue,
    formatRawJson,
    maskFieldValue,
    sensitivityLabel,
} from '@/features/document-requests/staff/review/document-extraction-helpers';
import {
    keyedCells,
    keyedFieldsList,
    keyedRows,
    keyedStrings,
    keyedTablesList,
} from '@/features/document-requests/staff/review/document-extraction-keys';
import type { DocumentExtraction } from '@/features/document-requests/types';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Renders Bedrock-structured OCR fields, tables, and notes, with raw JSON collapsed.
 * Sensitive fields start masked; confidence badges use Textract LINE match scores.
 */
export default function DocumentExtractionView({
    extraction,
    rawJson,
}: {
    extraction: DocumentExtraction | null;
    rawJson: string | null;
}) {
    const { t } = useTranslation();
    // Per-field reveal toggles — keys are stable field fingerprints.
    const [revealedFields, setRevealedFields] = useState<
        Record<string, boolean>
    >({});

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
    const documentConfidence = extraction?.confidence ?? null;
    // Build stable keys once so list keys are not bare map indexes.
    const keyedFields = keyedFieldsList(fields);
    const keyedTables = keyedTablesList(tables);
    const keyedNotes = keyedStrings(notes);

    return (
        <div className="space-y-8">
            {(documentType !== null ||
                summary !== null ||
                documentConfidence !== null) && (
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
                    {documentConfidence !== null && (
                        <p className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                            <span className="font-medium text-foreground">
                                {t('OCR confidence')}:
                            </span>
                            <ConfidenceBadge confidence={documentConfidence} />
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
                        {keyedFields.map(({ field, key }) => {
                            const isSensitive = field.sensitivity !== null;
                            const isRevealed = revealedFields[key] === true;
                            const showValue = !isSensitive || isRevealed;

                            return (
                                <div
                                    key={key}
                                    className="grid gap-1 px-3 py-2 sm:grid-cols-[minmax(8rem,14rem)_1fr] sm:gap-4"
                                >
                                    {/* OCR field labels are document content — do not translate. */}
                                    <dt className="flex flex-wrap items-center gap-2 text-sm font-medium text-muted-foreground">
                                        <span>{field.label}</span>
                                        {isSensitive && (
                                            <Badge variant="outline">
                                                {sensitivityLabel(
                                                    field.sensitivity,
                                                    t,
                                                )}
                                            </Badge>
                                        )}
                                    </dt>
                                    <dd className="flex flex-wrap items-start gap-2 text-sm">
                                        <span className="min-w-0 flex-1 whitespace-pre-wrap">
                                            {showValue
                                                ? formatFieldValue(field.value)
                                                : maskFieldValue(field.value)}
                                        </span>
                                        <span className="flex shrink-0 items-center gap-1">
                                            {field.confidence !== null && (
                                                <ConfidenceBadge
                                                    confidence={
                                                        field.confidence
                                                    }
                                                />
                                            )}
                                            {isSensitive && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-7 px-2"
                                                    onClick={() =>
                                                        setRevealedFields(
                                                            (current) => ({
                                                                ...current,
                                                                [key]: !isRevealed,
                                                            }),
                                                        )
                                                    }
                                                    aria-label={
                                                        isRevealed
                                                            ? t(
                                                                  'Hide sensitive value',
                                                              )
                                                            : t(
                                                                  'Reveal sensitive value',
                                                              )
                                                    }
                                                >
                                                    {isRevealed ? (
                                                        <EyeOff className="size-3.5" />
                                                    ) : (
                                                        <Eye className="size-3.5" />
                                                    )}
                                                </Button>
                                            )}
                                        </span>
                                    </dd>
                                </div>
                            );
                        })}
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
