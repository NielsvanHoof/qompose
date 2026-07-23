import { cn } from '@/lib/utils';

export default function Heading({
    title,
    description,
    variant = 'default',
    level,
    className,
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
    /** Explicit heading level — page titles use 1, section titles use 2. */
    level: 1 | 2;
    className?: string;
}) {
    const TitleTag = level === 1 ? 'h1' : 'h2';

    return (
        <header
            className={cn(
                variant === 'small' ? '' : 'mb-8 space-y-0.5',
                className,
            )}
        >
            <TitleTag
                className={
                    variant === 'small'
                        ? 'mb-0.5 text-base font-medium'
                        : 'text-xl font-semibold tracking-tight text-balance'
                }
            >
                {title}
            </TitleTag>
            {description && (
                <p className="text-sm text-muted-foreground">{description}</p>
            )}
        </header>
    );
}
