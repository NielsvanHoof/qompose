<?php

declare(strict_types=1);

namespace App\Services\Ocr\Normalization;

use App\Contracts\Ocr\DocumentExtraction;
use App\Enums\ExtractionFieldSensitivity;

use function is_array;

/**
 * Rule-based PII tagging for structured OCR fields (Dutch + common intl labels).
 * Prefer label keywords; fall back to value shape for BSN / IBAN / email.
 *
 * @phpstan-import-type DocumentExtractionField from DocumentExtraction
 * @phpstan-import-type DocumentExtractionPayload from DocumentExtraction
 */
final class FieldSensitivityClassifier
{
    /**
     * @param  DocumentExtractionPayload  $payload
     * @return DocumentExtractionPayload
     */
    public function classify(array $payload): array
    {
        $payload['fields'] = array_map(
            fn (array $field): array => $this->classifyField($field),
            $payload['fields'],
        );

        return $payload;
    }

    /**
     * @param  DocumentExtractionField  $field
     * @return DocumentExtractionField
     */
    private function classifyField(array $field): array
    {
        $label = mb_strtolower($field['label']);
        $values = is_array($field['value']) ? $field['value'] : [$field['value']];
        $joinedValues = implode(' ', array_filter(
            $values,
            static fn (string $value): bool => $value !== '',
        ));

        $field['sensitivity'] = $this->detectSensitivity($label, $joinedValues)?->value;

        return $field;
    }

    private function detectSensitivity(string $label, string $value): ?ExtractionFieldSensitivity
    {
        if ($this->labelMatches($label, [
            'bsn',
            'burgerservicenummer',
            'sofi',
            'sofinummer',
            'social security',
        ])) {
            return ExtractionFieldSensitivity::Bsn;
        }

        if ($this->labelMatches($label, ['iban'])) {
            return ExtractionFieldSensitivity::Iban;
        }

        if ($this->labelMatches($label, [
            'rekeningnummer',
            'bankrekening',
            'account number',
            'account no',
            'bank account',
        ])) {
            return ExtractionFieldSensitivity::AccountNumber;
        }

        if ($this->labelMatches($label, [
            'paspoort',
            'passport',
            'rijbewijs',
            'id number',
            'id-nummer',
            'documentnummer',
            'document number',
            'identity number',
            'legitimatienummer',
        ])) {
            return ExtractionFieldSensitivity::IdNumber;
        }

        if ($this->labelMatches($label, [
            'geboortedatum',
            'date of birth',
            'birth date',
            'geboren',
            'dob',
        ])) {
            return ExtractionFieldSensitivity::DateOfBirth;
        }

        if ($this->labelMatches($label, ['email', 'e-mail', 'mailadres'])) {
            return ExtractionFieldSensitivity::Email;
        }

        if ($this->labelMatches($label, [
            'telefoon',
            'phone',
            'mobiel',
            'mobile',
            'gsm',
        ])) {
            return ExtractionFieldSensitivity::Phone;
        }

        // Value-shape fallbacks when labels are vague ("Nummer", "ID", …).
        $compact = preg_replace('/\s+/u', '', $value) ?? $value;

        if (preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/i', $compact) === 1) {
            return ExtractionFieldSensitivity::Iban;
        }

        if (preg_match('/^\d{9}$/', $compact) === 1
            && ($this->labelMatches($label, ['nummer', 'number', 'nr']) || $label === 'bsn')
        ) {
            return ExtractionFieldSensitivity::Bsn;
        }

        if (filter_var(mb_trim($value), FILTER_VALIDATE_EMAIL) !== false) {
            return ExtractionFieldSensitivity::Email;
        }

        return null;
    }

    /**
     * @param  list<string>  $needles
     */
    private function labelMatches(string $label, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($label, $needle)) {
                return true;
            }
        }

        return false;
    }
}
