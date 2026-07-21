import type { ReactNode } from 'react';
import BrandShieldWatermark from '@/components/brand-shield-watermark';
import { cn } from '@/lib/utils';

type BrandHeroPanelProps = {
    children: ReactNode;
    className?: string;
};

/**
 * Marketing-site hero panel with Qompose shield watermarks on a brand background.
 */
export default function BrandHeroPanel({
    children,
    className,
}: BrandHeroPanelProps) {
    return (
        <div
            className={cn(
                'relative isolate overflow-hidden bg-primary text-primary-foreground',
                className,
            )}
        >
            <BrandShieldWatermark
                variant="primary"
                className="-bottom-24 -left-16 h-[min(70%,28rem)] w-[min(120%,36rem)] opacity-90"
            />
            <BrandShieldWatermark
                variant="light"
                className="-top-12 -right-20 h-[min(55%,22rem)] w-[min(90%,30rem)] opacity-35"
            />
            <div className="relative z-10 flex h-full flex-col">{children}</div>
        </div>
    );
}
