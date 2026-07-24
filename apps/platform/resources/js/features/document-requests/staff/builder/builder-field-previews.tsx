import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Disabled client-control previews shown on the form builder canvas.
 * These mirror portal controls without importing portal components.
 */

export function BuilderFilePreview({
    title,
    instructions,
}: {
    title: string;
    instructions?: string | null;
}) {
    const { t } = useTranslation();

    return (
        <div className="space-y-2">
            <FieldChrome title={title} instructions={instructions} />
            <div className="rounded-md border border-dashed border-border bg-muted/40 px-3 py-4 text-center text-sm text-muted-foreground">
                {t('Client uploads a file here')}
            </div>
            <Input
                type="file"
                disabled
                aria-hidden="true"
                className="pointer-events-none"
                tabIndex={-1}
            />
        </div>
    );
}

export function BuilderTextPreview({
    title,
    instructions,
}: {
    title: string;
    instructions?: string | null;
}) {
    const { t } = useTranslation();

    return (
        <div className="space-y-2">
            <FieldChrome title={title} instructions={instructions} />
            <Input
                type="text"
                disabled
                placeholder={t('Client types their answer here…')}
                className="bg-muted/40 text-muted-foreground"
                aria-hidden="true"
                tabIndex={-1}
            />
        </div>
    );
}

export function BuilderTextareaPreview({
    title,
    instructions,
}: {
    title: string;
    instructions?: string | null;
}) {
    const { t } = useTranslation();

    return (
        <div className="space-y-2">
            <FieldChrome title={title} instructions={instructions} />
            <textarea
                disabled
                rows={4}
                placeholder={t('Client writes a longer answer here…')}
                className="w-full rounded-md border border-input bg-muted/40 px-3 py-2 text-sm text-muted-foreground shadow-xs"
                aria-hidden="true"
                tabIndex={-1}
            />
        </div>
    );
}

export function BuilderDatePreview({
    title,
    instructions,
}: {
    title: string;
    instructions?: string | null;
}) {
    const { t } = useTranslation();

    return (
        <div className="space-y-2">
            <FieldChrome title={title} instructions={instructions} />
            <Input
                type="date"
                disabled
                className="bg-muted/40 text-muted-foreground"
                aria-label={t('Client picks a date here')}
                tabIndex={-1}
            />
        </div>
    );
}

export function BuilderNumberPreview({
    title,
    instructions,
}: {
    title: string;
    instructions?: string | null;
}) {
    const { t } = useTranslation();

    return (
        <div className="space-y-2">
            <FieldChrome title={title} instructions={instructions} />
            <Input
                type="number"
                disabled
                placeholder={t('Client enters a number here…')}
                className="bg-muted/40 text-muted-foreground"
                aria-hidden="true"
                tabIndex={-1}
            />
        </div>
    );
}

export function BuilderBooleanPreview({
    title,
    instructions,
}: {
    title: string;
    instructions?: string | null;
}) {
    const { t } = useTranslation();

    return (
        <div className="space-y-2">
            <FieldChrome title={title} instructions={instructions} />
            <div className="flex gap-2" aria-hidden="true">
                <span className="rounded-md border border-input bg-muted/40 px-3 py-1.5 text-sm text-muted-foreground">
                    {t('Yes')}
                </span>
                <span className="rounded-md border border-input bg-muted/40 px-3 py-1.5 text-sm text-muted-foreground">
                    {t('No')}
                </span>
            </div>
        </div>
    );
}

function FieldChrome({
    title,
    instructions,
}: {
    title: string;
    instructions?: string | null;
}) {
    return (
        <div className="min-w-0 space-y-1">
            <Label className="text-sm font-medium text-pretty">{title}</Label>
            {instructions ? (
                <p className="text-xs text-pretty text-muted-foreground">
                    {instructions}
                </p>
            ) : null}
        </div>
    );
}
