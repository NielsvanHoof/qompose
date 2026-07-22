import { Form } from '@inertiajs/react';
import {
    CalendarClock,
    MailCheck,
    MousePointerClick,
    Send,
} from 'lucide-react';
import DossierReminderController from '@/actions/App/Http/Controllers/Dossiers/DossierReminderController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { Dossier } from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/format-date-time';

export default function DossierFollowUpCard({
    dossier,
    canSendReminder,
}: {
    dossier: Dossier;
    canSendReminder: boolean;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    return (
        <Card className="overflow-hidden border-primary/15">
            <CardContent className="grid gap-0 p-0 lg:grid-cols-[1fr_1fr_1fr_auto]">
                <FollowUpDatum
                    icon={CalendarClock}
                    label={t('Due and owner')}
                    value={
                        dossier.due_date
                            ? new Date(
                                  `${dossier.due_date}T00:00:00`,
                              ).toLocaleDateString()
                            : t('No due date')
                    }
                    detail={dossier.responsible_staff?.name ?? t('Unassigned')}
                />
                <FollowUpDatum
                    icon={MailCheck}
                    label={t('Last sent')}
                    value={
                        dossier.last_client_message_sent_at
                            ? formatDateTime(
                                  dossier.last_client_message_sent_at,
                              )
                            : t('Not sent yet')
                    }
                    detail={
                        dossier.next_reminder_at
                            ? t('Next reminder :datetime', {
                                  datetime: formatDateTime(
                                      dossier.next_reminder_at,
                                  ),
                              })
                            : t('No reminder scheduled')
                    }
                />
                <FollowUpDatum
                    icon={MousePointerClick}
                    label={t('Last opened')}
                    value={
                        dossier.last_client_opened_at
                            ? formatDateTime(dossier.last_client_opened_at)
                            : t('Not opened yet')
                    }
                    detail={
                        dossier.reminder_interval_days
                            ? t('Automatic every :days days', {
                                  days: dossier.reminder_interval_days,
                              })
                            : t('Automatic reminders off')
                    }
                />

                <div className="flex items-center border-t bg-muted/20 p-4 lg:border-t-0 lg:border-l">
                    {canSendReminder && dossier.has_outstanding_client_items ? (
                        <Form
                            {...DossierReminderController.store.form({
                                tenant: currentWorkspace,
                                dossier: dossier.id,
                            })}
                        >
                            {({ errors, processing }) => (
                                <div className="grid gap-1">
                                    <Button disabled={processing}>
                                        <Send aria-hidden="true" />
                                        {t('Send reminder')}
                                    </Button>
                                    <InputError message={errors.dossier} />
                                </div>
                            )}
                        </Form>
                    ) : (
                        <p className="max-w-44 text-sm text-muted-foreground">
                            {dossier.has_outstanding_client_items
                                ? t(
                                      'You cannot send reminders for this dossier.',
                                  )
                                : t('No client follow-up is needed.')}
                        </p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

function FollowUpDatum({
    icon: Icon,
    label,
    value,
    detail,
}: {
    icon: typeof CalendarClock;
    label: string;
    value: string;
    detail: string;
}) {
    return (
        <div className="flex min-w-0 gap-3 border-t p-4 first:border-t-0 lg:border-t-0 lg:border-l lg:first:border-l-0">
            <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                <Icon className="size-4" aria-hidden="true" />
            </span>
            <span className="min-w-0">
                <span className="block text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    {label}
                </span>
                <span className="block truncate text-sm font-medium">
                    {value}
                </span>
                <span className="block truncate text-xs text-muted-foreground">
                    {detail}
                </span>
            </span>
        </div>
    );
}
