<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tenants\ProvisionTenant;
use App\Http\Requests\Workspace\StoreWorkspaceRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;

final class WorkspaceOnboardingController extends Controller
{
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

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Workspace created.',
        ]);

        return to_route('workspaces.dossiers.index', $tenant);
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
