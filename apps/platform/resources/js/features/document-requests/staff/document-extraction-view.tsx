import { Button } from '@/components/ui/button';
import EmptyState from '@/components/empty-state';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import type { DocumentExtraction } from '@/features/document-requests/types';

/**
 * Renders AnalyzeDocument key/values and tables, with raw JSON collapsed.
 */
export default function DocumentExtractionView({
    extraction,
    rawJson,
}: {
    extraction: DocumentExtraction | null;
    rawJson: string | null;
}) {
    if (extraction === null && (rawJson === null || rawJson === '')) {
        return (
            <EmptyState title="No extraction data is available for this file yet." />
        );
    }

    const keyValues = extraction?.key_values ?? {};
    const tables = extraction?.tables ?? [];
    const keyEntries = Object.entries(keyValues);

    return (
        <div className="space-y-8">
            <section className="space-y-3">
                <h2 className="text-lg font-semibold tracking-tight">Fields</h2>
                {keyEntries.length === 0 ? (
                    <EmptyState title="No form fields detected." />
                ) : (
                    <dl className="divide-y rounded-md border">
                        {keyEntries.map(([key, value]) => (
                            <div
                                key={key}
                                className="grid gap-1 px-3 py-2 sm:grid-cols-[minmax(8rem,14rem)_1fr] sm:gap-4"
                            >
                                <dt className="text-sm font-medium text-muted-foreground">
                                    {key}
                                </dt>
                                <dd className="text-sm whitespace-pre-wrap">
                                    {formatFieldValue(value)}
                                </dd>
                            </div>
                        ))}
                    </dl>
                )}
            </section>

            <section className="space-y-3">
                <h2 className="text-lg font-semibold tracking-tight">Tables</h2>
                {tables.length === 0 ? (
                    <EmptyState title="No tables detected." />
                ) : (
                    <div className="space-y-4">
                        {tables.map((table) => {
                            // Build stable string ids once so list keys are not bare map indexes.
                            const tableId = `table:${JSON.stringify(table)}`;

                            return (
                                <div
                                    key={tableId}
                                    className="overflow-x-auto rounded-md border"
                                >
                                    <table className="w-full min-w-md border-collapse text-left text-sm">
                                        <tbody>
                                            {table.map((row) => {
                                                const rowId = `${tableId}|${JSON.stringify(row)}`;
                                                // Unique keys per cell when a row repeats the same value.
                                                const cells = keyedCells(row);

                                                return (
                                                    <tr
                                                        key={rowId}
                                                        className="border-b last:border-b-0"
                                                    >
                                                        {cells.map(
                                                            ({ cell, key }) => (
                                                                <td
                                                                    key={`${rowId}|${key}`}
                                                                    className="px-3 py-2 align-top whitespace-pre-wrap"
                                                                >
                                                                    {cell}
                                                                </td>
                                                            ),
                                                        )}
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            );
                        })}
                    </div>
                )}
            </section>

            {rawJson !== null && rawJson !== '' && (
                <Collapsible className="space-y-2">
                    <CollapsibleTrigger asChild>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="px-0"
                        >
                            Raw JSON
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
    const seen = new Map<string, number>();

    return row.map((cell) => {
        const occurrence = (seen.get(cell) ?? 0) + 1;
        seen.set(cell, occurrence);

        return { cell, key: `${cell}#${occurrence}` };
    });
}
