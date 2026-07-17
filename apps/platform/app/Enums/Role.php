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
