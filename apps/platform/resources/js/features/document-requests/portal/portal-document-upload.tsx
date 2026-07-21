import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import ClientPortalUploadController from '@/actions/App/Http/Controllers/Portal/ClientPortalUploadController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { PortalDocumentRequest } from '@/features/document-requests/types';
import { useTranslation } from '@/hooks/use-translation';
import { inlinePortalActionOptions } from '@/lib/inline-portal-action-options';

/**
 * Client-portal file upload for a single document request.
 * Uses the restricted client portal session, not staff auth.
 */
export default function PortalDocumentUpload({
    documentRequest,
}: {
    documentRequest: PortalDocumentRequest;
}) {
    const { t } = useTranslation();
    const form = useForm<{ document: File | null }>({
        document: null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post(
            ClientPortalUploadController.store.url({
                documentRequest: documentRequest.id,
            }),
            {
                ...inlinePortalActionOptions,
                forceFormData: true,
                onSuccess: () => form.reset('document'),
            },
        );
    };

    return (
        <form onSubmit={submit} className="mt-3 space-y-2">
            <div className="flex flex-wrap items-end gap-2">
                <div className="grid min-w-48 flex-1 gap-1">
                    <Label htmlFor={`document-${documentRequest.id}`}>
                        {documentRequest.uploaded_document
                            ? t('Replace file')
                            : t('Upload file')}
                    </Label>
                    <Input
                        id={`document-${documentRequest.id}`}
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
                    className="h-1.5 w-full accent-primary"
                />
            )}
            <InputError message={form.errors.document} />
        </form>
    );
}
