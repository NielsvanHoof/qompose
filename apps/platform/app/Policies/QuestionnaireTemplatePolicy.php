<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\QuestionnaireTemplate;
use App\Models\Tenant;
use App\Models\User;

final class QuestionnaireTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewDossiers->value);
    }

    public function view(User $user, QuestionnaireTemplate $questionnaireTemplate): bool
    {
        return $user->can(Permission::ViewDossiers->value)
            && $this->isVisibleToUser($user, $questionnaireTemplate);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManageTemplates->value);
    }

    public function update(User $user, QuestionnaireTemplate $questionnaireTemplate): bool
    {
        return $user->can(Permission::ManageTemplates->value)
            && ! $questionnaireTemplate->isSystem()
            && $this->ownsTenantTemplate($user, $questionnaireTemplate);
    }

    public function delete(User $user, QuestionnaireTemplate $questionnaireTemplate): bool
    {
        return $this->update($user, $questionnaireTemplate);
    }

    /**
     * Copy a system or firm template into the current firm.
     */
    public function copy(User $user, QuestionnaireTemplate $questionnaireTemplate): bool
    {
        return $user->can(Permission::ManageTemplates->value)
            && $this->isVisibleToUser($user, $questionnaireTemplate);
    }

    /**
     * Apply a visible template onto a dossier the user can create for.
     */
    public function apply(User $user, QuestionnaireTemplate $questionnaireTemplate): bool
    {
        return $user->can(Permission::CreateDossiers->value)
            && $this->isVisibleToUser($user, $questionnaireTemplate);
    }

    private function isVisibleToUser(User $user, QuestionnaireTemplate $template): bool
    {
        if ($template->isSystem()) {
            return true;
        }

        $tenant = $template->tenant;

        return $tenant instanceof Tenant && $user->belongsToTenant($tenant);
    }

    private function ownsTenantTemplate(User $user, QuestionnaireTemplate $template): bool
    {
        $tenant = Tenant::current();

        return $tenant instanceof Tenant
            && $template->tenant_id === $tenant->getKey()
            && $user->belongsToTenant($tenant);
    }
}
