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
    const [copiedText, copy] = useCopyToClipboard();
    const copied = copiedText === portalUrl;

    const copyPortalLink = async () => {
        await copy(portalUrl);
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
                    {copied ? t('Copied') : t('Copy portal link')}
                </Button>
            </CardContent>
        </Card>
    );
}
