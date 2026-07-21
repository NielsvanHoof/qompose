import { brandShields } from '@/lib/brand-shields';
import { cn } from '@/lib/utils';

type BrandShieldWatermarkProps = {
    variant: keyof typeof brandShields;
    className?: string;
};

/**
 * Decorative Qompose shield watermark for branded panels and headers.
 */
export default function BrandShieldWatermark({
    variant,
    className,
}: BrandShieldWatermarkProps) {
    return (
        <img
            src={brandShields[variant]}
            alt=""
            aria-hidden
            className={cn('pointer-events-none absolute object-contain', className)}
        />
    );
}
