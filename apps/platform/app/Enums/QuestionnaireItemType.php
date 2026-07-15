<?php

declare(strict_types=1);

namespace App\Enums;

enum QuestionnaireItemType: string
{
    case File = 'file';
    case Text = 'text';
    case Boolean = 'boolean';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
