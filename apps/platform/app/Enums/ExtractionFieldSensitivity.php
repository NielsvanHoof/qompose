<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * PII / sensitive categories tagged onto structured OCR fields.
 * Used by the extraction UI to mask values until staff reveal them.
 */
enum ExtractionFieldSensitivity: string
{
    case Bsn = 'bsn';
    case Iban = 'iban';
    case IdNumber = 'id_number';
    case AccountNumber = 'account_number';
    case DateOfBirth = 'date_of_birth';
    case Email = 'email';
    case Phone = 'phone';
}
