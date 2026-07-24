import { Badge } from '@/components/ui/badge';
import type { ExtractionFieldSensitivity } from '@/features/document-requests/types';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Soft visual cue for Textract LINE confidence (0–1).
 * Low scores need human review.
 */
export function ConfidenceBadge({ confidence }: { confidence: number }) {
    const { t } = useTranslation();
    const percent = Math.round(confidence * 100);
    const variant =
        confidence < 0.8
            ? 'destructive'
            : confidence < 0.9
              ? 'outline'
              : 'secondary';

    return (
        <Badge variant={variant} title={t('OCR confidence from Textract')}>
            {percent}%
        </Badge>
    );
}

/** Human label for a PII sensitivity category. */
export function sensitivityLabel(
    sensitivity: ExtractionFieldSensitivity | null,
    t: (key: string) => string,
): string {
    switch (sensitivity) {
        case 'bsn':
            return t('BSN');
        case 'iban':
            return t('IBAN');
        case 'id_number':
            return t('ID number');
        case 'account_number':
            return t('Account number');
        case 'date_of_birth':
            return t('Date of birth');
        case 'email':
            return t('Email');
        case 'phone':
            return t('Phone');
        default:
            return t('Sensitive');
    }
}

export function formatFieldValue(value: string | string[]): string {
    return Array.isArray(value) ? value.join(', ') : value;
}

/** Bullet mask — keep length hint without leaking digits. */
export function maskFieldValue(value: string | string[]): string {
    if (Array.isArray(value)) {
        return value.map(() => '••••••••').join(', ');
    }

    const length = Math.min(Math.max(value.length, 4), 12);

    return '•'.repeat(length);
}

export function formatRawJson(raw: string): string {
    try {
        return JSON.stringify(JSON.parse(raw), null, 2);
    } catch {
        return raw;
    }
}
