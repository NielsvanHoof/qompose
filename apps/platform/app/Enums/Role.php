<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Owner = 'owner';
    case Administrator = 'administrator';
    case Adviser = 'adviser';
    case Reviewer = 'reviewer';
    case ReadOnly = 'read-only';

    /**
     * Roles that can be assigned via invite / role change forms.
     *
     * @return list<self>
     */
    public static function assignable(): array
    {
        return [
            self::Administrator,
            self::Adviser,
            self::Reviewer,
            self::ReadOnly,
            self::Owner,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Owner => __('Owner'),
            self::Administrator => __('Administrator'),
            self::Adviser => __('Adviser'),
            self::Reviewer => __('Reviewer'),
            self::ReadOnly => __('Read-only'),
        };
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => Permission::cases(),
            self::Administrator => [
                Permission::ManageClients,
                Permission::CreateDossiers,
                Permission::ViewDossiers,
                Permission::ManageTemplates,
                Permission::ManageMembers,
                Permission::ReviewDocuments,
                Permission::DownloadDocuments,
                Permission::ViewAuditLog,
            ],
            self::Adviser => [
                Permission::ViewDossiers,
                Permission::CreateDossiers,
                Permission::ReviewDocuments,
                Permission::DownloadDocuments,
            ],
            self::Reviewer => [
                Permission::ViewDossiers,
                Permission::ReviewDocuments,
            ],
            self::ReadOnly => [
                Permission::ViewDossiers,
            ],
        };
    }
}
