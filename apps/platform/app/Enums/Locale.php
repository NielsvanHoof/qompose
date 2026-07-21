<?php

declare(strict_types=1);

namespace App\Enums;

enum Locale: string
{
    case English = 'en';
    case Dutch = 'nl';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Dutch => 'Nederlands',
        };
    }
}
