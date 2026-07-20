<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Who is submitting a document request answer or upload.
 * Portal and staff paths enforce different status rules in DocumentRequestTransitions.
 */
enum SubmissionContext: string
{
    case Portal = 'portal';
    case Staff = 'staff';
}
