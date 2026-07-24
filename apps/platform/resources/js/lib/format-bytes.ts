/**
 * Format a byte count for display (B / KB / MB).
 * Shared by dossier, portal, and media library views.
 * Uses a non-breaking space so the number and unit stay on one line.
 */
export function formatBytes(bytes: number): string {
    const nbsp = '\u00A0';

    if (bytes < 1024) {
        return `${bytes}${nbsp}B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)}${nbsp}KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)}${nbsp}MB`;
}
