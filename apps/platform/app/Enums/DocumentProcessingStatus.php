<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentProcessingStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
        };
    }

    /**
     * Statuses that mean OCR is still in flight (UI should poll).
     */
    public function isInFlight(): bool
    {
        return $this === self::Pending || $this === self::Processing;
    }
}
