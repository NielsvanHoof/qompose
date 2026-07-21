import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { getQuestionnaireItemTypeDefinitions } from '@/features/document-requests/questionnaire-item-type-registry';
import type { QuestionnaireItemType } from '@/features/document-requests/types';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Shared type selector keeps values and labels consistent in every editor.
 */
export default function QuestionnaireItemTypeSelect({
    defaultValue,
    error,
    id,
}: {
    defaultValue: QuestionnaireItemType;
    error?: string;
    id?: string;
}) {
    const { t } = useTranslation();
    const typeDefinitions = getQuestionnaireItemTypeDefinitions(t);

    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{t('Type')}</Label>
            <Select required defaultValue={defaultValue} name="type">
                <SelectTrigger id={id} className="w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent className="bg-background">
                    {typeDefinitions.map((type) => (
                        <SelectItem key={type.value} value={type.value}>
                            {type.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <InputError message={error} />
        </div>
    );
}
