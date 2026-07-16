<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Actions\Tenancy\ProvisionTenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenancy\StoreWorkspaceRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class WorkspaceOnboardingController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('onboarding/firm');
    }

    public function store(
        StoreWorkspaceRequest $request,
        ProvisionTenant $provisionTenant,
    ): RedirectResponse {
        $name = $request->validated('name');
        $tenant = $provisionTenant(
            $name,
            $request->authenticatedUser(),
            $this->availableSlug($name),
        );

        $request->session()->put('active_tenant_id', $tenant->id);
        $request->session()->put('ensure_valid_tenant_session_tenant_id', $tenant->id);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Firm created. Add your first client to get started.',
        ]);

        return to_route('workspaces.clients.create');
    }

    private function availableSlug(string $name): string
    {
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = 'workspace';
        }

        $slugs = Tenant::query()->get(['slug'])->pluck('slug');
        $slug = $baseSlug;
        $suffix = 2;

        while ($slugs->contains($slug)) {
            $suffixValue = (string) $suffix;
            $slug = Str::limit($baseSlug, 255 - mb_strlen($suffixValue) - 1, '')
                .'-'.$suffixValue;
            $suffix++;
        }

        return $slug;
    }
}
