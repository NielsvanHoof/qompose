import DatePicker from '@/components/date-picker';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ResponsibleStaffOption } from '@/features/dossiers/types';
import { useTranslation } from '@/hooks/use-translation';

export default function DossierFollowUpFields({
    responsibleStaff,
    defaultDueDate = '',
    defaultResponsibleUserId = null,
    defaultReminderIntervalDays = null,
    errors,
}: {
    responsibleStaff: ResponsibleStaffOption[];
    defaultDueDate?: string;
    defaultResponsibleUserId?: number | null;
    defaultReminderIntervalDays?: number | null;
    errors: Partial<Record<string, string>>;
}) {
    const { t } = useTranslation();

    return (
        <fieldset className="space-y-4 rounded-lg border bg-muted/20 p-4">
            <legend className="px-1 text-sm font-semibold">
                {t('Follow-up planning')}
            </legend>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="due_date">{t('Due date')}</Label>
                    <DatePicker
                        id="due_date"
                        name="due_date"
                        defaultValue={defaultDueDate}
                        invalid={Boolean(errors.due_date)}
                    />
                    <InputError message={errors.due_date} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="responsible_user_id">
                        {t('Responsible staff member')}
                    </Label>
                    <select
                        id="responsible_user_id"
                        name="responsible_user_id"
                        defaultValue={
                            defaultResponsibleUserId?.toString() ?? ''
                        }
                        className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">{t('Unassigned')}</option>
                        {responsibleStaff.map((staffMember) => (
                            <option key={staffMember.id} value={staffMember.id}>
                                {staffMember.name} · {staffMember.email}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.responsible_user_id} />
                </div>
            </div>

            <div className="grid gap-2 sm:max-w-xs">
                <Label htmlFor="reminder_interval_days">
                    {t('Automatic reminder cadence')}
                </Label>
                <div className="flex items-center gap-2">
                    <Input
                        id="reminder_interval_days"
                        name="reminder_interval_days"
                        type="number"
                        min="1"
                        max="30"
                        defaultValue={defaultReminderIntervalDays ?? ''}
                    />
                    <span className="text-sm text-muted-foreground">
                        {t('days')}
                    </span>
                </div>
                <p className="text-xs text-muted-foreground">
                    {t(
                        'Leave empty to disable scheduled reminders. The cadence starts after the first client email.',
                    )}
                </p>
                <InputError message={errors.reminder_interval_days} />
            </div>
        </fieldset>
    );
}
