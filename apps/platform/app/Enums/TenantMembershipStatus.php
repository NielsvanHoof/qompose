<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantMembershipStatus: string
{
    case Invited = 'invited';
    case Active = 'active';
    case Suspended = 'suspended';
}
