<?php

declare(strict_types=1);

namespace App\Enums;

enum DossierReminderSource: string
{
    case Manual = 'manual';
    case Scheduled = 'scheduled';
}
