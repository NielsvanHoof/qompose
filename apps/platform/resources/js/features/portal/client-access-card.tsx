import { Form, router } from '@inertiajs/react';
import { useState } from 'react';
import ClientAccessGrantController from '@/actions/App/Http/Controllers/Portal/ClientAccessGrantController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import type { AccessGrant } from '@/features/portal/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { inlineDossierActionOptions } from '@/lib/inline-dossier-action-options';

/**
 * Lists access grants and creates a new 7-day portal link (optionally emailed).
 */
export default function ClientAccessCard({
    dossierId,
    clientName,
    clientEmail,
    accessGrants,
    canCreate,
}: {
    dossierId: number;
    clientName: string;
    clientEmail: string;
    accessGrants: AccessGrant[];
    canCreate: boolean;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const [sendInvite, setSendInvite] = useState(true);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Client access</CardTitle>
                <CardDescription>
                    Email a magic link so {clientName} can upload documents
                    without an account.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {accessGrants.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No access grants yet.
                    </p>
                ) : (
                    <div className="divide-y rounded-md border">
                        {accessGrants.map((grant) => (
                            <div
                                key={grant.id}
                                className="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
                            >
                                <div className="text-sm">
                                    <p>
                                        Expires{' '}
                                        {new Date(
                                            grant.expires_at,
                                        ).toLocaleString()}
                                    </p>
                                    <p className="text-muted-foreground">
                                        {grant.is_valid
                                            ? 'Active'
                                            : grant.revoked_at
                                              ? 'Revoked'
                                              : 'Expired'}
                                        {grant.last_used_at &&
                                            ` · Last used ${new Date(grant.last_used_at).toLocaleString()}`}
                                    </p>
                                </div>
                                {grant.is_valid && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.delete(
                                                ClientAccessGrantController.destroy.url(
                                                    {
                                                        tenant: currentWorkspace,
                                                        grant: grant.id,
                                                    },
                                                ),
                                                inlineDossierActionOptions,
                                            )
                                        }
                                    >
                                        Revoke
                                    </Button>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                {canCreate && (
                    <Form
                        {...ClientAccessGrantController.store.form({
                            tenant: currentWorkspace,
                            dossier: dossierId,
                        })}
                        className="space-y-3"
                    >
                        {({ processing }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="expires_in_days"
                                    value="7"
                                />
                                {/* Checkbox posts "1" when checked; omitted when unchecked. */}
                                <input
                                    type="hidden"
                                    name="send_invite"
                                    value={sendInvite ? '1' : '0'}
                                />
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="send_invite"
                                        checked={sendInvite}
                                        onCheckedChange={(checked) =>
                                            setSendInvite(checked === true)
                                        }
                                    />
                                    <Label htmlFor="send_invite">
                                        Email invite to {clientEmail}
                                    </Label>
                                </div>
                                <Button
                                    disabled={processing}
                                    variant="secondary"
                                >
                                    {sendInvite
                                        ? 'Create link & email client'
                                        : 'Create 7-day portal link'}
                                </Button>
                            </>
                        )}
                    </Form>
                )}
            </CardContent>
        </Card>
    );
}
