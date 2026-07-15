<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditEvent: string
{
    case DossierViewed = 'dossier.viewed';
    case DocumentRequestCreated = 'document_request.created';
    case DocumentUploaded = 'document.uploaded';
    case DocumentViewed = 'document.viewed';
    case DocumentDownloaded = 'document.downloaded';
    case AccessDenied = 'access.denied';
    case MemberRoleChanged = 'member.role_changed';

    public function label(): string
    {
        return match ($this) {
            self::DossierViewed => 'Dossier viewed',
            self::DocumentRequestCreated => 'Document request created',
            self::DocumentUploaded => 'Document uploaded',
            self::DocumentViewed => 'Document viewed',
            self::DocumentDownloaded => 'Document downloaded',
            self::AccessDenied => 'Access denied',
            self::MemberRoleChanged => 'Member role changed',
        };
    }
}
