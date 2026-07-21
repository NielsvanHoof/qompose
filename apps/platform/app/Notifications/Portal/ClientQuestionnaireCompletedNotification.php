<?php

declare(strict_types=1);

namespace App\Notifications\Portal;

use Illuminate\Notifications\Notification;

/**
 * Persist a workspace inbox item when a client finishes every questionnaire item.
 * Real-time push stays on ClientQuestionnaireCompleted (Reverb); this is the DB source of truth.
 */
final class ClientQuestionnaireCompletedNotification extends Notification
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $dossierId,
        public readonly string $dossierTitle,
        public readonly string $clientName,
        public readonly string $message,
        public readonly string $dossierUrl,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{
     *     tenant_id: string,
     *     type: string,
     *     message: string,
     *     dossier_id: int,
     *     dossier_title: string,
     *     client_name: string,
     *     dossier_url: string
     * }
     */
    public function toArray(object $notifiable): array
    {
        return [
            // String so pgsql data->>'tenant_id' comparisons stay text = text.
            'tenant_id' => (string) $this->tenantId,
            'type' => 'client_questionnaire_completed',
            'message' => $this->message,
            'dossier_id' => $this->dossierId,
            'dossier_title' => $this->dossierTitle,
            'client_name' => $this->clientName,
            'dossier_url' => $this->dossierUrl,
        ];
    }
}
