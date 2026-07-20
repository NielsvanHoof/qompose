import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import UploadedDocumentController from '@/actions/App/Http/Controllers/Dossiers/UploadedDocumentController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { DocumentRequest } from '@/features/document-requests/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { inlineDossierActionOptions } from '@/lib/inline-dossier-action-options';

/**
 * Staff fallback file upload (email / walk-in).
 * Shown only after expanding “Upload/Replace on behalf of client”.
 */
export default function DocumentRequestUpload({
    dossierId,
    documentRequest,
}: {
    dossierId: number;
    documentRequest: DocumentRequest;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const form = useForm<{ document: File | null }>({
        document: null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post(
            UploadedDocumentController.store.url({
                tenant: currentWorkspace,
                dossier: dossierId,
                documentRequest: documentRequest.id,
            }),
            {
                ...inlineDossierActionOptions,
                forceFormData: true,
                onSuccess: () => form.reset('document'),
            },
        );
    };

    return (
        <form onSubmit={submit} className="mt-2 space-y-2">
            <div className="flex flex-wrap items-end gap-2">
                <div className="grid min-w-48 flex-1 gap-1">
                    <Label htmlFor={`document-${documentRequest.id}`}>
                        {documentRequest.uploaded_document
                            ? 'Replace file'
                            : 'Upload file'}
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
                    {form.processing ? 'Uploading…' : 'Upload'}
                </Button>
            </div>
            {form.progress && (
                <progress
                    value={form.progress.percentage}
                    max={100}
                    aria-label="Upload progress"
                    className="h-1.5 w-full"
                />
            )}
            <InputError message={form.errors.document} />
        </form>
    );
}
