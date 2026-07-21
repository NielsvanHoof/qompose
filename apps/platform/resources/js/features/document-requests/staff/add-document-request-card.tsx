import { Form } from '@inertiajs/react';
import DocumentRequestController from '@/actions/App/Http/Controllers/Dossiers/DocumentRequestController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import QuestionnaireItemTypeSelect from '@/features/document-requests/questionnaire-item-type-select';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { inlineDossierActionOptions } from '@/lib/inline-dossier-action-options';

/**
 * Sidebar form to add one questionnaire item to a dossier.
 */
export default function AddDocumentRequestCard({
    dossierId,
}: {
    dossierId: number;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('Add request')}</CardTitle>
                <CardDescription>
                    {t(
                        'Ask for a file, a text answer, or a yes/no confirmation.',
                    )}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    {...DocumentRequestController.store.form({
                        tenant: currentWorkspace,
                        dossier: dossierId,
                    })}
                    options={inlineDossierActionOptions}
                    resetOnSuccess
                    className="space-y-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <QuestionnaireItemTypeSelect
                                id="type"
                                defaultValue="file"
                                error={errors.type}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="title">{t('Title')}</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    required
                                    placeholder={t('Payslip January 2025')}
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="instructions">
                                    {t('Instructions (optional)')}
                                </Label>
                                <textarea
                                    id="instructions"
                                    name="instructions"
                                    rows={4}
                                    className="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    placeholder={t(
                                        'Upload the PDF you received from your employer.',
                                    )}
                                />
                                <InputError message={errors.instructions} />
                            </div>

                            <Button disabled={processing} className="w-full">
                                {t('Add request')}
                            </Button>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
