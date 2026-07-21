import type { ComponentType } from 'react';
import {
    PortalBooleanRequestContent,
    type PortalDocumentRequestTypeProps,
    PortalFileRequestContent,
    PortalTextRequestContent,
} from '@/features/document-requests/portal/portal-document-request-type-content';
import {
    StaffBooleanRequestContent,
    type StaffDocumentRequestTypeProps,
    StaffFileRequestContent,
    StaffTextRequestContent,
} from '@/features/document-requests/staff/document-request-type-content';
import type { QuestionnaireItemType } from '@/features/document-requests/types';

type QuestionnaireItemTypeDefinition = {
    value: QuestionnaireItemType;
    /** English translation key — resolve with `t()` before display. */
    label: string;
    StaffContent: ComponentType<StaffDocumentRequestTypeProps>;
    PortalContent: ComponentType<PortalDocumentRequestTypeProps>;
};

/**
 * This registry is the frontend source of truth for questionnaire item types.
 * The exhaustive record forces every new type to provide both renderers.
 * Labels are English keys — resolve with `t()` (or via helpers below).
 */
const questionnaireItemTypeRegistry = {
    file: {
        value: 'file',
        label: 'File upload',
        StaffContent: StaffFileRequestContent,
        PortalContent: PortalFileRequestContent,
    },
    text: {
        value: 'text',
        label: 'Text answer',
        StaffContent: StaffTextRequestContent,
        PortalContent: PortalTextRequestContent,
    },
    boolean: {
        value: 'boolean',
        label: 'Yes / no',
        StaffContent: StaffBooleanRequestContent,
        PortalContent: PortalBooleanRequestContent,
    },
} satisfies Record<QuestionnaireItemType, QuestionnaireItemTypeDefinition>;

/**
 * Ordered definitions with English label keys (not yet translated).
 * Prefer `getQuestionnaireItemTypeDefinitions(t)` when rendering labels.
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
    }));
}

export function getQuestionnaireItemTypeDefinition(
    type: QuestionnaireItemType,
): QuestionnaireItemTypeDefinition {
    return questionnaireItemTypeRegistry[type];
}
