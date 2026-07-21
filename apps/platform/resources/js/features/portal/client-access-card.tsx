import { Form, router } from '@inertiajs/react';
import { useState } from 'react';
import ClientAccessGrantController from '@/actions/App/Http/Controllers/Portal/ClientAccessGrantController';
import EmptyState from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import type { AccessGrant } from '@/features/portal/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { formatDateTime } from '@/lib/format-date-time';
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
                    <EmptyState title="No access grants yet." />
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
                                        {formatDateTime(grant.expires_at)}
                                    </p>
                                    <p className="text-muted-foreground">
                                        {grant.is_valid
                                            ? 'Active'
                                            : grant.revoked_at
                                              ? 'Revoked'
                                              : 'Expired'}
                                        {grant.last_used_at &&
                                            ` · Last used ${formatDateTime(grant.last_used_at)}`}
                                    </p>
                                </div>
                                {grant.is_valid && (
                                    <RevokeAccessGrantButton
                                        grantId={grant.id}
                                    />
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
                                    type="submit"
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

function RevokeAccessGrantButton({ grantId }: { grantId: number }) {
    const currentWorkspace = useCurrentWorkspace();

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button type="button" variant="outline" size="sm">
                    Revoke
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Revoke portal access?</DialogTitle>
                    <DialogDescription>
                        The client will no longer be able to use this link. You
                        can create a new one anytime.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        variant="destructive"
                        onClick={() =>
                            router.delete(
                                ClientAccessGrantController.destroy.url({
                                    tenant: currentWorkspace,
                                    grant: grantId,
                                }),
                                inlineDossierActionOptions,
                            )
                        }
                    >
                        Revoke access
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
