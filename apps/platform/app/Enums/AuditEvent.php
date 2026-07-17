<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditEvent: string
{
    case DossierViewed = 'dossier.viewed';
    case DocumentRequestCreated = 'document_request.created';
    case DocumentRequestAccepted = 'document_request.accepted';
    case DocumentRequestRejected = 'document_request.rejected';
    case DocumentUploaded = 'document.uploaded';
    case QuestionnaireAnswerSubmitted = 'questionnaire.answer_submitted';
    case DossierCompleted = 'dossier.completed';
    case DocumentViewed = 'document.viewed';
    case DocumentDownloaded = 'document.downloaded';
    case DocumentProcessingStarted = 'document.processing_started';
    case DocumentProcessingCompleted = 'document.processing_completed';
    case DocumentProcessingFailed = 'document.processing_failed';
    case ClientPortalAccessGrantCreated = 'client_portal.access_grant_created';
    case ClientPortalAccessGrantRevoked = 'client_portal.access_grant_revoked';
    case ClientPortalAccessed = 'client_portal.accessed';
    case ClientPortalInviteQueued = 'client_portal.invite_queued';
    case ClientPortalInviteSent = 'client_portal.invite_sent';
    case ClientPortalInviteFailed = 'client_portal.invite_failed';
    case ClientChangesRequestedQueued = 'client_portal.changes_requested_queued';
    case ClientChangesRequestedSent = 'client_portal.changes_requested_sent';
    case ClientChangesRequestedFailed = 'client_portal.changes_requested_failed';
    case AccessDenied = 'access.denied';
    case MemberRoleChanged = 'member.role_changed';

    public function label(): string
    {
        return match ($this) {
            self::DossierViewed => 'Dossier viewed',
            self::DocumentRequestCreated => 'Document request created',
            self::DocumentRequestAccepted => 'Document request accepted',
            self::DocumentRequestRejected => 'Document request rejected',
            self::DocumentUploaded => 'Document uploaded',
            self::QuestionnaireAnswerSubmitted => 'Questionnaire answer submitted',
            self::DossierCompleted => 'Dossier completed',
            self::DocumentViewed => 'Document viewed',
            self::DocumentDownloaded => 'Document downloaded',
            self::DocumentProcessingStarted => 'Document processing started',
            self::DocumentProcessingCompleted => 'Document processing completed',
            self::DocumentProcessingFailed => 'Document processing failed',
            self::ClientPortalAccessGrantCreated => 'Client portal access grant created',
            self::ClientPortalAccessGrantRevoked => 'Client portal access grant revoked',
            self::ClientPortalAccessed => 'Client portal accessed',
            self::ClientPortalInviteQueued => 'Client portal invitation queued',
            self::ClientPortalInviteSent => 'Client portal invitation sent',
            self::ClientPortalInviteFailed => 'Client portal invitation failed',
            self::ClientChangesRequestedQueued => 'Client changes-requested notification queued',
            self::ClientChangesRequestedSent => 'Client changes-requested notification sent',
            self::ClientChangesRequestedFailed => 'Client changes-requested notification failed',
            self::AccessDenied => 'Access denied',
            self::MemberRoleChanged => 'Member role changed',
        };
    }
}
