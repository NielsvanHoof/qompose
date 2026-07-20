<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Models\Tenant;

/**
 * Single entry point for running code under a tenant in async and console paths.
 * HTTP requests set the tenant via middleware instead.
 */
final class TenantContext
{
    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function runForTenant(Tenant $tenant, callable $callback): mixed
    {
        return $tenant->execute($callback);
    }
}
