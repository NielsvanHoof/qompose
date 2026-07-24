import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { getQuestionnaireItemTypeDefinitions } from '@/features/document-requests/questionnaire-item-type-registry';
import type { BuilderSaveStatus } from '@/features/document-requests/staff/builder/use-questionnaire-builder-actions';
import type {
    DocumentRequest,
    QuestionnaireItemType,
} from '@/features/document-requests/types';
import { useTranslation } from '@/hooks/use-translation';

type SettingsSaveInput = {
    type: QuestionnaireItemType;
    title: string;
    instructions: string | null;
};

/**
 * Autosaving field settings for the selected canvas component.
 * Type saves immediately. Title and instructions save on blur.
 */
export default function QuestionnaireBuilderSettings({
    documentRequest,
    canEdit,
    saveStatus,
    onSave,
}: {
    documentRequest: DocumentRequest | null;
    canEdit: boolean;
    saveStatus: BuilderSaveStatus;
    onSave: (input: SettingsSaveInput) => Promise<boolean>;
}) {
    const { t } = useTranslation();
    const definitions = getQuestionnaireItemTypeDefinitions(t);
    const [title, setTitle] = useState(documentRequest?.title ?? '');
    const [instructions, setInstructions] = useState(
        documentRequest?.instructions ?? '',
    );
    const [type, setType] = useState<QuestionnaireItemType>(
        documentRequest?.type ?? 'file',
    );
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        setTitle(documentRequest?.title ?? '');
        setInstructions(documentRequest?.instructions ?? '');
        setType(documentRequest?.type ?? 'file');
        setError(null);
    }, [documentRequest]);

    if (!documentRequest) {
        return (
            <aside
                className="rounded-2xl border border-dashed border-border/70 bg-muted/20 p-4"
                aria-label={t('Field settings')}
            >
                <h3 className="text-sm font-semibold">{t('Field settings')}</h3>
                <p className="mt-1 text-xs text-muted-foreground">
                    {t('Select a field on the canvas to edit its settings.')}
                </p>
            </aside>
        );
    }

    const persist = async (next: SettingsSaveInput) => {
        if (!canEdit) {
            return;
        }

        const trimmedTitle = next.title.trim();

        if (trimmedTitle === '') {
            setError(t('Enter a field label.'));

            return;
        }

        setError(null);
        const ok = await onSave({
            ...next,
            title: trimmedTitle,
            instructions: next.instructions?.trim() || null,
        });

        if (!ok) {
            setError(t('Could not save. Check the fields and try again.'));
        }
    };

    const saveLabel =
        saveStatus === 'saving'
            ? t('Saving…')
            : saveStatus === 'saved'
              ? t('Saved')
              : saveStatus === 'error'
                ? t('Save failed')
                : null;

    const blurSave = () => void persist({ type, title, instructions });

    return (
        <aside
            className="space-y-4 rounded-2xl border border-border/70 bg-card p-4"
            aria-label={t('Field settings')}
        >
            <div className="flex items-start justify-between gap-2">
                <div>
                    <h3 className="text-sm font-semibold">
                        {t('Field settings')}
                    </h3>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {t('Changes save automatically.')}
                    </p>
                </div>
                <p
                    className="font-data text-xs text-muted-foreground"
                    aria-live="polite"
                >
                    {saveLabel}
                </p>
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`builder-type-${documentRequest.id}`}>
                    {t('Type')}
                </Label>
                <Select
                    value={type}
                    disabled={!canEdit}
                    onValueChange={(value) => {
                        const nextType = value as QuestionnaireItemType;
                        setType(nextType);
                        void persist({ type: nextType, title, instructions });
                    }}
                >
                    <SelectTrigger
                        id={`builder-type-${documentRequest.id}`}
                        className="w-full"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent className="bg-background">
                        {definitions.map((definition) => (
                            <SelectItem
                                key={definition.value}
                                value={definition.value}
                            >
                                {definition.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`builder-title-${documentRequest.id}`}>
                    {t('Label')}
                </Label>
                <Input
                    id={`builder-title-${documentRequest.id}`}
                    value={title}
                    disabled={!canEdit}
                    autoComplete="off"
                    onChange={(event) => setTitle(event.target.value)}
                    onBlur={blurSave}
                />
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`builder-instructions-${documentRequest.id}`}>
                    {t('Instructions')}
                </Label>
                <textarea
                    id={`builder-instructions-${documentRequest.id}`}
                    rows={4}
                    value={instructions}
                    disabled={!canEdit}
                    onChange={(event) => setInstructions(event.target.value)}
                    onBlur={blurSave}
                    className="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50"
                    placeholder={t('Optional help text shown to the client…')}
                />
            </div>

            <InputError message={error ?? undefined} />
        </aside>
    );
}
