/**
 * Format an ISO timestamp the same on SSR and in the browser.
 * Uses UTC so server/client timezones cannot diverge during hydration.
 */
export function formatDateTime(iso: string): string {
    return new Intl.DateTimeFormat('en-GB', {
        dateStyle: 'short',
        timeStyle: 'medium',
        timeZone: 'UTC',
    }).format(new Date(iso));
}
