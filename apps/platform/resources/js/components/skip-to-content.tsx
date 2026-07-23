import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

/**
 * First focusable control in a layout. Lets keyboard users bypass chrome.
 */
export default function SkipToContent({
    targetId = 'main-content',
    className,
}: {
    targetId?: string;
    className?: string;
}) {
    const { t } = useTranslation();

    return (
        <a
            href={`#${targetId}`}
            className={cn(
                'sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-100 focus:rounded-md focus:bg-background focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-foreground focus:shadow-lg focus:ring-2 focus:ring-ring',
                className,
            )}
        >
            {t('Skip to content')}
        </a>
    );
}
