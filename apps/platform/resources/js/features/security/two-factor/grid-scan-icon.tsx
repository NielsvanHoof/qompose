import { ScanLine } from 'lucide-react';

const GRID_LINES = ['1', '2', '3', '4', '5'] as const;

/**
 * Decorative QR-scan glyph for the two-factor setup dialog header.
 */
export default function GridScanIcon() {
    return (
        <div className="mb-3 rounded-full border border-border bg-card p-0.5 shadow-sm">
            <div className="relative overflow-hidden rounded-full border border-border bg-muted p-2.5">
                <div className="absolute inset-0 grid grid-cols-5 opacity-50">
                    {GRID_LINES.map((line) => (
                        <div
                            key={`grid-col-${line}`}
                            className="border-r border-border last:border-r-0"
                        />
                    ))}
                </div>
                <div className="absolute inset-0 grid grid-rows-5 opacity-50">
                    {GRID_LINES.map((line) => (
                        <div
                            key={`grid-row-${line}`}
                            className="border-b border-border last:border-b-0"
                        />
                    ))}
                </div>
                <ScanLine className="relative z-20 size-6 text-foreground" />
            </div>
        </div>
    );
}
