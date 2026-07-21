import { Head, setLayoutProps } from '@inertiajs/react';
import TemplateShowContent from '@/features/questionnaires/template-show-content';
import type {
    TemplateCategoryOption,
    TemplateDetail,
} from '@/features/questionnaires/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import {
    show as showTemplate,
    index as templateIndex,
} from '@/routes/workspaces/templates';

/**
 * View or edit a questionnaire template and its items.
 */
export default function ShowTemplate({
    template,
    categories,
    can_manage: canManage,
    can_copy: canCopy,
}: {
    template: TemplateDetail;
    categories: TemplateCategoryOption[];
    can_manage: boolean;
    can_copy: boolean;
}) {
    const currentWorkspace = useCurrentWorkspace();

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Templates',
                href: templateIndex(currentWorkspace),
            },
            {
                title: template.name,
                href: showTemplate({
                    tenant: currentWorkspace,
                    template: template.id,
                }),
            },
        ],
    });

    return (
        <>
            <Head title={template.name} />
            <TemplateShowContent
                template={template}
                categories={categories}
                canManage={canManage}
                canCopy={canCopy}
            />
        </>
    );
}
