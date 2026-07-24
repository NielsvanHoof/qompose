import { useEffect, useRef, useState } from 'react';
import { useCopyToClipboard } from 'usehooks-ts';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslation } from '@/hooks/use-translation';

/** How long the button shows "Copied" before reverting. */
const COPIED_FEEDBACK_MS = 2000;

/**
 * One-time flash of the client portal URL after creating an access grant.
 * The plain token cannot be retrieved again after this page load.
 */
export default function PortalLinkBanner({
    portalUrl,
    token = null,
}: {
    portalUrl: string;
    token?: string | null;
}) {
    const { t } = useTranslation();
    const [, copy] = useCopyToClipboard();
    // Local flag so the label resets after a short delay (clipboard lib keeps the value).
    const [justCopied, setJustCopied] = useState(false);
    const resetTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        return () => {
            if (resetTimeoutRef.current !== null) {
                clearTimeout(resetTimeoutRef.current);
            }
        };
    }, []);

    const copyPortalLink = async () => {
        await copy(portalUrl);
        setJustCopied(true);

        if (resetTimeoutRef.current !== null) {
            clearTimeout(resetTimeoutRef.current);
        }

        resetTimeoutRef.current = setTimeout(() => {
            setJustCopied(false);
            resetTimeoutRef.current = null;
        }, COPIED_FEEDBACK_MS);
    };

    return (
        <Card className="border-primary/40">
            <CardHeader>
                <CardTitle>{t('Client portal link')}</CardTitle>
                <CardDescription>
                    {t(
                        'Copy this link now. The plain token is shown once and cannot be retrieved again.',
                    )}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
                <code className="block max-w-full overflow-x-auto rounded-md bg-muted px-3 py-2 text-sm break-all">
                    {portalUrl}
                </code>
                {token && (
                    <p className="text-xs text-muted-foreground">
                        {t('Token: :token…', { token: token.slice(0, 8) })}
                    </p>
                )}
                <Button
                    type="button"
                    variant="outline"
                    onClick={copyPortalLink}
                    aria-live="polite"
                >
                    {justCopied ? t('Copied') : t('Copy portal link')}
                </Button>
            </CardContent>
        </Card>
    );
}
