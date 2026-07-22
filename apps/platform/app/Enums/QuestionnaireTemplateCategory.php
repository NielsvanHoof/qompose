<?php

declare(strict_types=1);

namespace App\Enums;

enum QuestionnaireTemplateCategory: string
{
    case Kyc = 'kyc';
    case Jaarrekening = 'jaarrekening';
    case Fiscale = 'fiscale';
    case Pbc = 'pbc';
    case Custom = 'custom';

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
            self::Kyc => __('KYC'),
            self::Jaarrekening => __('Annual accounts'),
            self::Fiscale => __('Tax return'),
            self::Pbc => __('PBC'),
            self::Custom => __('Custom'),
        };
    }
}
