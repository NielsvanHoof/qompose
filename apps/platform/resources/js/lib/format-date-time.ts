/**
 * Format an ISO timestamp the same on SSR and in the browser.
 * Uses UTC so server/client timezones cannot diverge during hydration.
 * Pass the current Inertia locale (from useTranslation) for language.
 */
export function formatDateTime(iso: string, locale: string): string {
    return new Intl.DateTimeFormat(locale, {
        dateStyle: 'short',
        timeStyle: 'medium',
        timeZone: 'UTC',
    }).format(new Date(iso));
}

/**
 * Format a calendar date (YYYY-MM-DD or ISO) without a time component.
 * Same locale/UTC rules as formatDateTime so due dates match the rest of the UI.
 */
export function formatDate(iso: string, locale: string): string {
    return new Intl.DateTimeFormat(locale, {
        dateStyle: 'short',
        timeZone: 'UTC',
    }).format(new Date(iso));
}
