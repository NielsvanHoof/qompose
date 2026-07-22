<?php

declare(strict_types=1);

namespace App\Data\Notifications;

/**
 * One staff inbox notification row.
 */
final readonly class WorkspaceNotificationItemData
{
    public function __construct(
        public string $id,
        public string $type,
        public string $message,
        public int $dossierId,
        public string $dossierTitle,
        public string $clientName,
        public string $dossierUrl,
        public ?string $readAt,
        public string $createdAt,
    ) {}

    /**
     * @return array{
     *     id: string,
     *     type: string,
     *     message: string,
     *     dossier_id: int,
     *     dossier_title: string,
     *     client_name: string,
     *     dossier_url: string,
     *     read_at: string|null,
     *     created_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'message' => $this->message,
            'dossier_id' => $this->dossierId,
            'dossier_title' => $this->dossierTitle,
            'client_name' => $this->clientName,
            'dossier_url' => $this->dossierUrl,
            'read_at' => $this->readAt,
            'created_at' => $this->createdAt,
        ];
    }
}
