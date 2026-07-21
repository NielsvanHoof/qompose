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
    case ClientQuestionnaireCompleted = 'client_portal.questionnaire_completed';
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
            self::DossierViewed => __('Dossier viewed'),
            self::DocumentRequestCreated => __('Document request created'),
            self::DocumentRequestAccepted => __('Document request accepted'),
            self::DocumentRequestRejected => __('Document request rejected'),
            self::DocumentUploaded => __('Document uploaded'),
            self::QuestionnaireAnswerSubmitted => __('Questionnaire answer submitted'),
            self::ClientQuestionnaireCompleted => __('Client questionnaire completed'),
            self::DossierCompleted => __('Dossier completed'),
            self::DocumentViewed => __('Document viewed'),
            self::DocumentDownloaded => __('Document downloaded'),
            self::DocumentProcessingStarted => __('Document processing started'),
            self::DocumentProcessingCompleted => __('Document processing completed'),
            self::DocumentProcessingFailed => __('Document processing failed'),
            self::ClientPortalAccessGrantCreated => __('Client portal access grant created'),
            self::ClientPortalAccessGrantRevoked => __('Client portal access grant revoked'),
            self::ClientPortalAccessed => __('Client portal accessed'),
            self::ClientPortalInviteQueued => __('Client portal invitation queued'),
            self::ClientPortalInviteSent => __('Client portal invitation sent'),
            self::ClientPortalInviteFailed => __('Client portal invitation failed'),
            self::ClientChangesRequestedQueued => __('Client changes-requested notification queued'),
            self::ClientChangesRequestedSent => __('Client changes-requested notification sent'),
            self::ClientChangesRequestedFailed => __('Client changes-requested notification failed'),
            self::AccessDenied => __('Access denied'),
            self::MemberRoleChanged => __('Member role changed'),
        };
    }
}
