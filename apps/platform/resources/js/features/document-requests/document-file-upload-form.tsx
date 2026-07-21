import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';

type VisitOptions = Record<string, unknown>;

/**
 * Shared file picker + progress UI for staff and portal uploads.
 * Callers supply the Wayfinder URL and visit options (forceFormData is always set).
 */
export default function DocumentFileUploadForm({
    inputId,
    hasExistingUpload,
    actionUrl,
    visitOptions = {},
    className = 'mt-2 space-y-2',
    progressClassName = 'h-1.5 w-full',
}: {
    inputId: string;
    hasExistingUpload: boolean;
    actionUrl: string;
    visitOptions?: VisitOptions;
    className?: string;
    progressClassName?: string;
}) {
    const { t } = useTranslation();
    const form = useForm<{ document: File | null }>({
        document: null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post(actionUrl, {
            ...visitOptions,
            forceFormData: true,
            onSuccess: () => form.reset('document'),
        });
    };

    return (
        <form onSubmit={submit} className={className}>
            <div className="flex flex-wrap items-end gap-2">
                <div className="grid min-w-48 flex-1 gap-1">
                    <Label htmlFor={inputId}>
                        {hasExistingUpload
                            ? t('Replace file')
                            : t('Upload file')}
                    </Label>
                    <Input
                        id={inputId}
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                        onChange={(event) =>
                            form.setData(
                                'document',
                                event.target.files?.[0] ?? null,
                            )
                        }
                    />
                </div>
                <Button
                    type="submit"
                    size="sm"
                    disabled={form.processing || !form.data.document}
                >
                    {form.processing ? t('Uploading…') : t('Upload')}
                </Button>
            </div>
            {form.progress && (
                <progress
                    value={form.progress.percentage}
                    max={100}
                    aria-label={t('Upload progress')}
                    className={progressClassName}
                />
            )}
            <InputError message={form.errors.document} />
        </form>
    );
}
