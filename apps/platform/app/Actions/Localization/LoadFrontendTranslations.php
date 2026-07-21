<?php

declare(strict_types=1);

namespace App\Actions\Localization;

use App\Enums\Locale;
use Illuminate\Support\Facades\File;

use function in_array;
use function is_array;

final class LoadFrontendTranslations
{
    /**
     * @return array<string, string>
     */
    public function handle(string $locale): array
    {
        if (! in_array($locale, Locale::values(), true)) {
            $locale = Locale::English->value;
        }

        $path = lang_path("{$locale}.json");

        if (! File::exists($path)) {
            return [];
        }

        /** @var array<string, string>|null $translations */
        $translations = json_decode(File::get($path), true);

        return is_array($translations) ? $translations : [];
    }
}
