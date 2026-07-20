<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Audit\LogAuditActivity;
use App\Enums\AuditEvent;
use App\Models\DocumentRequest;
use App\Models\Tenant;
use App\Notifications\Portal\ClientChangesRequestedNotification;
use App\Tenancy\TenantContext;
use Illuminate\Notifications\Events\NotificationSent;
use RuntimeException;
use Throwable;

final class LogClientChangesRequestedSent
{
    public function __construct(
        private readonly LogAuditActivity $logAuditActivity,
        private readonly TenantContext $tenantContext,
    ) {}

    public function handle(NotificationSent $event): void
    {
        if (
            ! $event->notification instanceof ClientChangesRequestedNotification
            || $event->channel !== 'mail'
        ) {
            return;
        }

        try {
            $documentRequest = DocumentRequest::query()
                ->withoutGlobalScopes()
                ->whereKey($event->notification->documentRequestId)
                ->first();

            if (! $documentRequest instanceof DocumentRequest) {
                return;
            }

            $tenant = Tenant::query()->whereKey($documentRequest->tenant_id)->first();

            if (! $tenant instanceof Tenant) {
                return;
            }

            $this->tenantContext->runForTenant($tenant, function () use ($documentRequest, $event): void {
                $this->logAuditActivity->handle(
                    AuditEvent::ClientChangesRequestedSent,
                    $documentRequest,
                    [
                        'dossier_id' => $documentRequest->dossier_id,
                        'channel' => $event->channel,
                    ],
                    includeRequestContext: false,
                );
            });
        } catch (Throwable $exception) {
            report(new RuntimeException(
                'Failed to audit a delivered client changes-requested notification.',
                previous: $exception,
            ));
        }
    }
}
