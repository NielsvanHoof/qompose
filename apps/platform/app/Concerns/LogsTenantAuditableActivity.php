<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;

trait LogsTenantAuditableActivity
{
    public function beforeActivityLogged(Model $activity, string $event): void
    {
        if ($activity->getAttribute('tenant_id') !== null) {
            return;
        }

        if (isset($this->tenant_id)) {
            $activity->setAttribute('tenant_id', $this->tenant_id);
        }
    }

    /**
     * @return list<string>
     */
    protected function auditableAttributes(): array
    {
        return array_values(array_diff($this->getFillable(), ['tenant_id']));
    }
}
