import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

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
    const [copied, setCopied] = useState(false);

    const copyPortalLink = async () => {
        await navigator.clipboard.writeText(portalUrl);
        setCopied(true);
    };

    return (
        <Card className="border-primary/40">
            <CardHeader>
                <CardTitle>Client portal link</CardTitle>
                <CardDescription>
                    Copy this link now. The plain token is shown once and cannot
                    be retrieved again.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
                <code className="block max-w-full overflow-x-auto rounded-md bg-muted px-3 py-2 text-sm break-all">
                    {portalUrl}
                </code>
                {token && (
                    <p className="text-xs text-muted-foreground">
                        Token: {token.slice(0, 8)}…
                    </p>
                )}
                <Button
                    type="button"
                    variant="outline"
                    onClick={copyPortalLink}
                    aria-live="polite"
                >
                    {copied ? 'Copied' : 'Copy portal link'}
                </Button>
            </CardContent>
        </Card>
    );
}
