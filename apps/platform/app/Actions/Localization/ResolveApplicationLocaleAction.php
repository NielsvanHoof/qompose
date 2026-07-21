<?php

declare(strict_types=1);

namespace App\Actions\Localization;

use App\Enums\Locale;
use App\Models\User;
use Illuminate\Http\Request;

use function is_string;

final class ResolveApplicationLocaleAction
{
    public function handle(Request $request): string
    {
        $user = $request->user();

        if ($user instanceof User) {
            /** @var mixed $storedLocale */
            $storedLocale = $user->getAttributes()['locale'] ?? null;

            if (is_string($storedLocale) && $storedLocale !== '') {
                $userLocale = Locale::tryFrom($storedLocale);

                if ($userLocale instanceof Locale) {
                    return $userLocale->value;
                }
            }
        }

        $cookieValue = $request->cookie('locale', '');
        $cookieLocale = is_string($cookieValue) ? Locale::tryFrom($cookieValue) : null;

        if ($cookieLocale instanceof Locale) {
            return $cookieLocale->value;
        }

        $preferredLocale = $request->getPreferredLanguage(Locale::values());

        if (is_string($preferredLocale)) {
            return $preferredLocale;
        }

        $fallbackLocale = config('app.locale', Locale::English->value);

        return is_string($fallbackLocale) ? $fallbackLocale : Locale::English->value;
    }
}
