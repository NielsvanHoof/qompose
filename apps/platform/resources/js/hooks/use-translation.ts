import { usePage } from '@inertiajs/react';

type AvailableLocale = {
    code: string;
    label: string;
};

type TranslationReplacements = Record<string, string | number>;

export function useTranslation() {
    const { translations, locale, available_locales } = usePage().props;

    const t = (
        key: string,
        replacements: TranslationReplacements = {},
    ): string => {
        const dictionary = translations as Record<string, string>;
        let translation = dictionary[key] ?? key;

        for (const [placeholder, value] of Object.entries(replacements)) {
            translation = translation.replace(`:${placeholder}`, String(value));
        }

        return translation;
    };

    return {
        t,
        locale: locale as string,
        availableLocales: available_locales as AvailableLocale[],
    };
}
