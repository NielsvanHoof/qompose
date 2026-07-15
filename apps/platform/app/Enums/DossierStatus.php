<?php

declare(strict_types=1);

namespace App\Enums;

enum DossierStatus: string
{
    case Draft = 'draft';
    case AwaitingClient = 'awaiting_client';
    case InReview = 'in_review';
    case Completed = 'completed';
}
