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
            ->subject("{$this->firmName}: changes requested for {$this->dossierTitle}")
            ->greeting("Hello {$this->clientName},")
            ->line("{$this->firmName} has reviewed your submission for “{$this->dossierTitle}”.")
            ->line("Changes are needed for “{$this->documentRequestTitle}”.")
            ->line('Open the secure portal using the link from your original invitation to read the feedback and submit the corrected information.')
            ->line('For your security, the review feedback is only shown inside the portal.')
            ->line('If you did not expect this email, contact the firm directly.');
    }
}
