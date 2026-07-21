<?php

declare(strict_types=1);

namespace App\Notifications\Portal;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ClientChangesRequestedNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $documentRequestId,
        public int $dossierId,
        public string $clientName,
        public string $dossierTitle,
        public string $documentRequestTitle,
        public string $firmName,
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__(':firm: changes requested for :title', [
                'firm' => $this->firmName,
                'title' => $this->dossierTitle,
            ]))
            ->greeting(__('Hello :name,', [
                'name' => $this->clientName,
            ]))
            ->line(__(':firm has reviewed your submission for “:title”.', [
                'firm' => $this->firmName,
                'title' => $this->dossierTitle,
            ]))
            ->line(__('Changes are needed for “:item”.', [
                'item' => $this->documentRequestTitle,
            ]))
            ->line(__('Open the secure portal using the link from your original invitation to read the feedback and submit the corrected information.'))
            ->line(__('For your security, the review feedback is only shown inside the portal.'))
            ->line(__('If you did not expect this email, contact the firm directly.'));
    }
}
