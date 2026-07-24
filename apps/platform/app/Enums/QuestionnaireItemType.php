<?php

declare(strict_types=1);

namespace App\Enums;

enum QuestionnaireItemType: string
{
    case File = 'file';
    case Text = 'text';
    case Textarea = 'textarea';
    case Date = 'date';
    case Number = 'number';
    case Boolean = 'boolean';

    /**
     * Whether answers for this type are stored in answer_text.
     */
    public function storesAnswerText(): bool
    {
        return match ($this) {
            self::Text, self::Textarea, self::Date, self::Number => true,
            self::File, self::Boolean => false,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
