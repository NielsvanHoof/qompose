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
            self::Pending => __('Pending'),
            self::Processing => __('Processing'),
            self::Completed => __('Completed'),
            self::Failed => __('Failed'),
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
