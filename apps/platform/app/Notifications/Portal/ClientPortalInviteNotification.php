<?php

declare(strict_types=1);

namespace App\Notifications\Portal;

use App\Models\Client;
use App\Models\Dossier;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use RuntimeException;

final class ClientPortalInviteNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $grantId,
        public Dossier $dossier,
        public string $portalUrl,
        public CarbonInterface $expiresAt,
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
        $client = $this->dossier->client;

        if (! $client instanceof Client) {
            throw new RuntimeException('Dossier client is missing.');
        }

        return (new MailMessage)
            ->subject(__(':firm: documents requested for :title', [
                'firm' => $this->firmName,
                'title' => $this->dossier->title,
            ]))
            ->greeting(__('Hello :name,', [
                'name' => $client->name,
            ]))
            ->line(__(':firm has asked you to upload documents for “:title”.', [
                'firm' => $this->firmName,
                'title' => $this->dossier->title,
            ]))
            ->line(__('Open the secure portal with the button below. No account or password is required.'))
            ->action(__('Open document portal'), $this->portalUrl)
            ->line(__('This link expires on :expires.', [
                'expires' => $this->expiresAt->timezone(config('app.timezone'))->format('j F Y, H:i'),
            ]))
            ->line(__('If you did not expect this email, you can ignore it.'));
    }
}
