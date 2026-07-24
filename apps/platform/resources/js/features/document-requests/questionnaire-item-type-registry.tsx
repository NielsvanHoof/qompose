import type { LucideIcon } from 'lucide-react';
import {
    AlignLeft,
    Calendar,
    CheckSquare,
    FileUp,
    Hash,
    Type,
} from 'lucide-react';
import type { ComponentType } from 'react';
import {
    PortalBooleanRequestContent,
    type PortalDocumentRequestTypeProps,
    PortalFileRequestContent,
    PortalTextRequestContent,
} from '@/features/document-requests/portal/portal-document-request-type-content';
import {
    BuilderBooleanPreview,
    BuilderDatePreview,
    BuilderFilePreview,
    BuilderNumberPreview,
    BuilderTextareaPreview,
    BuilderTextPreview,
} from '@/features/document-requests/staff/builder/builder-field-previews';
import {
    StaffBooleanRequestContent,
    type StaffDocumentRequestTypeProps,
    StaffFileRequestContent,
    StaffTextRequestContent,
} from '@/features/document-requests/staff/review/document-request-type-content';
import type { QuestionnaireItemType } from '@/features/document-requests/types';

export type BuilderFieldPreviewProps = {
    title: string;
    instructions?: string | null;
};

type QuestionnaireItemTypeDefinition = {
    value: QuestionnaireItemType;
    /** English translation key — resolve with `t()` before display. */
    label: string;
    /** Short palette description key. */
    description: string;
    /** Default title used when dropping a new component. */
    defaultTitle: string;
    icon: LucideIcon;
    BuilderPreview: ComponentType<BuilderFieldPreviewProps>;
    StaffContent: ComponentType<StaffDocumentRequestTypeProps>;
    PortalContent: ComponentType<PortalDocumentRequestTypeProps>;
};

/**
 * Frontend source of truth for questionnaire item types.
 * Every type must supply builder, staff, and portal renderers.
 */
const questionnaireItemTypeRegistry = {
    file: {
        value: 'file',
        label: 'File upload',
        description: 'Ask the client to upload a document.',
        defaultTitle: 'New file upload',
        icon: FileUp,
        BuilderPreview: BuilderFilePreview,
        StaffContent: StaffFileRequestContent,
        PortalContent: PortalFileRequestContent,
    },
    text: {
        value: 'text',
        label: 'Text answer',
        description: 'Collect a short written answer.',
        defaultTitle: 'New text answer',
        icon: Type,
        BuilderPreview: BuilderTextPreview,
        StaffContent: StaffTextRequestContent,
        PortalContent: PortalTextRequestContent,
    },
    textarea: {
        value: 'textarea',
        label: 'Long text',
        description: 'Collect a longer written answer.',
        defaultTitle: 'New long text answer',
        icon: AlignLeft,
        BuilderPreview: BuilderTextareaPreview,
        // Same answer_text storage and review UI as short text.
        StaffContent: StaffTextRequestContent,
        PortalContent: PortalTextRequestContent,
    },
    date: {
        value: 'date',
        label: 'Date',
        description: 'Ask the client to pick a date.',
        defaultTitle: 'New date question',
        icon: Calendar,
        BuilderPreview: BuilderDatePreview,
        StaffContent: StaffTextRequestContent,
        PortalContent: PortalTextRequestContent,
    },
    number: {
        value: 'number',
        label: 'Number',
        description: 'Collect a numeric answer.',
        defaultTitle: 'New number question',
        icon: Hash,
        BuilderPreview: BuilderNumberPreview,
        StaffContent: StaffTextRequestContent,
        PortalContent: PortalTextRequestContent,
    },
    boolean: {
        value: 'boolean',
        label: 'Yes / no',
        description: 'Ask a yes or no confirmation.',
        defaultTitle: 'New yes / no question',
        icon: CheckSquare,
        BuilderPreview: BuilderBooleanPreview,
        StaffContent: StaffBooleanRequestContent,
        PortalContent: PortalBooleanRequestContent,
    },
} satisfies Record<QuestionnaireItemType, QuestionnaireItemTypeDefinition>;

/**
 * Ordered definitions with English label keys (not yet translated).
 */
export const questionnaireItemTypeDefinitions = Object.values(
    questionnaireItemTypeRegistry,
);

/**
 * Ordered definitions with labels resolved through the translation function.
 */
export function getQuestionnaireItemTypeDefinitions(
    t: (key: string) => string,
): QuestionnaireItemTypeDefinition[] {
    return questionnaireItemTypeDefinitions.map((def) => ({
        ...def,
        label: t(def.label),
        description: t(def.description),
        defaultTitle: t(def.defaultTitle),
    }));
}

export function getQuestionnaireItemTypeDefinition(
    type: QuestionnaireItemType,
): QuestionnaireItemTypeDefinition {
    return questionnaireItemTypeRegistry[type];
}
