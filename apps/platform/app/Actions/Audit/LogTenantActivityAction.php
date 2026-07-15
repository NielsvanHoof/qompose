<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Models\Activity;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Actions\LogActivityAction;

final class LogTenantActivityAction extends LogActivityAction
{
    protected function beforeActivityLogged(Model $activity): void
    {
        parent::beforeActivityLogged($activity);

        if ($activity->getAttribute('tenant_id') !== null) {
            return;
        }

        $tenantId = $this->resolveTenantId($activity);

        if ($tenantId !== null) {
            $activity->setAttribute('tenant_id', $tenantId);
        }
    }

    private function resolveTenantId(Model $activity): ?int
    {
        $currentTenant = Tenant::current();

        if ($currentTenant instanceof Tenant) {
            return $currentTenant->id;
        }

        if (! $activity instanceof Activity) {
            return null;
        }

        $subject = $activity->getRelationValue('subject');

        if ($subject instanceof Model) {
            $tenantId = $subject->getAttribute('tenant_id');

            if (is_int($tenantId)) {
                return $tenantId;
            }
        }

        return null;
    }
}
