<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ActiveTenantController extends Controller
{
    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->belongsToTenant($tenant)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $request->session()->put('active_tenant_id', $tenant->id);
        $request->session()->put('ensure_valid_tenant_session_tenant_id', $tenant->id);

        return to_route('dashboard');
    }
}
