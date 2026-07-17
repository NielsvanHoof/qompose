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
    label: string;
    StaffContent: ComponentType<StaffDocumentRequestTypeProps>;
    PortalContent: ComponentType<PortalDocumentRequestTypeProps>;
};

/**
 * This registry is the frontend source of truth for questionnaire item types.
 * The exhaustive record forces every new type to provide both renderers.
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
 * Ordered definitions are reused by every request type select.
 */
export const questionnaireItemTypeDefinitions = Object.values(
    questionnaireItemTypeRegistry,
);

export function getQuestionnaireItemTypeDefinition(
    type: QuestionnaireItemType,
): QuestionnaireItemTypeDefinition {
    return questionnaireItemTypeRegistry[type];
}
