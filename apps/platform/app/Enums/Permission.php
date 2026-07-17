<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    case ViewDossiers = 'dossiers.view';
    case CreateDossiers = 'dossiers.create';
    case ReviewDocuments = 'documents.review';
    case DownloadDocuments = 'documents.download';
    case ManageClients = 'clients.manage';
    case ManageTemplates = 'templates.manage';
    case ManageMembers = 'members.manage';
    case ManageTenantSettings = 'tenant.settings.manage';
    case ViewAuditLog = 'audit.view';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
