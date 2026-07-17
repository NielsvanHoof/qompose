<?php

declare(strict_types=1);

namespace App\Policies\Audit;

use App\Enums\Permission;
use App\Models\User;

final class ActivityPolicy
{
    /**
     * Whether the user may browse the tenant activity / audit log.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewAuditLog->value);
    }
}
