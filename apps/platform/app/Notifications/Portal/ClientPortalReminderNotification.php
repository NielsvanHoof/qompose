<?php

declare(strict_types=1);

namespace App\Notifications\Portal;

use App\Enums\DossierReminderSource;
use App\Models\Client;
use App\Models\Dossier;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use RuntimeException;

final class ClientPortalReminderNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $grantId,
        public Dossier $dossier,
        public string $portalUrl,
        public CarbonInterface $expiresAt,
        public string $firmName,
        public DossierReminderSource $source,
    ) {
        $this->afterCommit();
    }

    /** @return list<string> */
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

        $message = (new MailMessage)
            ->subject(__(':firm: reminder for :title', [
                'firm' => $this->firmName,
                'title' => $this->dossier->title,
            ]))
            ->greeting(__('Hello :name,', ['name' => $client->name]))
            ->line(__(':firm is still waiting for information for “:title”.', [
                'firm' => $this->firmName,
                'title' => $this->dossier->title,
            ]));

        if ($this->dossier->due_date !== null) {
            $message->line(__('Please complete the remaining items by :date.', [
                'date' => $this->dossier->due_date->format('j F Y'),
            ]));
        }

        return $message
            ->action(__('Continue in the secure portal'), $this->portalUrl)
            ->line(__('This new secure link expires on :expires.', [
                'expires' => $this->expiresAt->timezone(config('app.timezone'))->format('j F Y, H:i'),
            ]))
            ->line(__('If you have already provided everything, no further action is needed.'));
    }
}
