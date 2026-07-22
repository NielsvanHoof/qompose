import { useMediaQuery } from 'usehooks-ts';

const MOBILE_BREAKPOINT = 768;

/** Matches shadcn sidebar mobile breakpoint (viewport width below 768px). */
export function useIsMobile(): boolean {
    return useMediaQuery(`(max-width: ${MOBILE_BREAKPOINT - 1}px)`);
}
