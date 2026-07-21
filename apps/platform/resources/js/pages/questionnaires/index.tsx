import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import IndexQueryToolbar from '@/components/index-query/index-query-toolbar';
import { Button } from '@/components/ui/button';
import QuestionnaireTemplatesSection from '@/features/questionnaires/questionnaire-templates-section';
import type { TemplateSummary } from '@/features/questionnaires/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import {
    create as createTemplate,
    index as templateIndex,
} from '@/routes/workspaces/templates';
import type { IndexQueryConfig, Paginated } from '@/types/pagination';

/**
 * Template library — system packs plus firm-owned copies.
 */
export default function TemplateIndex({
    system_templates: systemTemplates,
    firm_templates: firmTemplates,
    can_manage: canManage,
    indexQuery,
}: {
    system_templates: Paginated<TemplateSummary>;
    firm_templates: Paginated<TemplateSummary>;
    can_manage: boolean;
    indexQuery: IndexQueryConfig;
    /** Current Spatie filter bag — consumed by useIndexQuery via usePage(). */
    filters?: Record<string, string>;
    sort?: string | null;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('Templates'),
                href: templateIndex(currentWorkspace),
            },
        ],
    });

    return (
        <>
            <Head title={t('Templates')} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        level={1}
                        className="mb-0"
                        title={t('Templates')}
                        description={t(
                            'Reusable KYC, jaarrekening, fiscale and PBC questionnaires. Copy a system pack to customise it for your firm.',
                        )}
                    />

                    {canManage && (
                        <Button asChild>
                            <Link href={createTemplate(currentWorkspace)}>
                                <Plus />
                                {t('New template')}
                            </Link>
                        </Button>
                    )}
                </div>

                {/* Shared filters/sort apply to both system and firm buckets. */}
                <IndexQueryToolbar config={indexQuery} />

                <QuestionnaireTemplatesSection
                    title={t('System templates')}
                    description={t(
                        'Read-only starter packs. Copy one to edit for your firm.',
                    )}
                    templates={systemTemplates}
                    canManage={canManage}
                    empty={t('No system templates seeded yet.')}
                />

                <QuestionnaireTemplatesSection
                    title={t('My templates')}
                    description={t(
                        'Firm-owned templates you can edit and apply to dossiers.',
                    )}
                    templates={firmTemplates}
                    canManage={canManage}
                    empty={t(
                        'No firm templates yet. Copy a system template or create one.',
                    )}
                />
            </div>
        </>
    );
}
