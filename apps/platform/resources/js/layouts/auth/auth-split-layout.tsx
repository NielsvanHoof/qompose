import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-shell/app-logo-icon';
import BrandHeroPanel from '@/components/brand-hero-panel';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;
    const { t } = useTranslation();

    return (
        <div className="relative grid min-h-svh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <BrandHeroPanel className="relative hidden h-full p-10 lg:flex dark:border-r">
                <Link
                    href={dashboard()}
                    className="flex items-center gap-3 text-lg font-semibold"
                >
                    <AppLogoIcon className="size-8 text-primary-foreground" />
                    {name}
                </Link>

                <div className="mt-auto max-w-md space-y-4 pb-4">
                    <p className="text-sm font-medium tracking-wide text-primary-foreground/80 uppercase">
                        {t('Secure document exchange')}
                    </p>
                    <h2 className="text-3xl leading-tight font-semibold text-balance">
                        {t('The smart, secure way to request client documents')}
                    </h2>
                    <p className="text-sm leading-relaxed text-primary-foreground/85">
                        {t(
                            'One professional environment for your team and clients to share documents with clarity, structure, and control.',
                        )}
                    </p>
                </div>
            </BrandHeroPanel>

            <div className="w-full lg:p-8">
                <div className="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <Link
                        href={dashboard()}
                        className="relative z-20 flex items-center justify-center lg:hidden"
                    >
                        <div className="flex size-12 items-center justify-center rounded-md bg-primary text-primary-foreground sm:size-14">
                            <AppLogoIcon className="size-7 text-primary-foreground sm:size-8" />
                        </div>
                    </Link>
                    <div className="flex flex-col items-start gap-2 text-left sm:items-center sm:text-center">
                        <h1 className="text-xl font-medium">{title}</h1>
                        <p className="text-sm text-balance text-muted-foreground">
                            {description}
                        </p>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
