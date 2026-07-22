<?php

declare(strict_types=1);

namespace App\Notifications\Tenancy;

use App\Enums\Role;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class WorkspaceMemberInviteNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $invitationId,
        public string $firmName,
        public Role $role,
        public string $acceptUrl,
        public CarbonInterface $expiresAt,
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
            ->subject(__('Join :firm on :app', [
                'firm' => $this->firmName,
                'app' => (string) config('app.name'),
            ]))
            ->greeting(__('You have been invited'))
            ->line(__(':firm invited you to join their workspace as :role.', [
                'firm' => $this->firmName,
                'role' => $this->role->label(),
            ]))
            ->line(__('This invitation expires on :date.', [
                'date' => $this->expiresAt->timezone(config('app.timezone'))->toDayDateTimeString(),
            ]))
            ->action(__('Accept invitation'), $this->acceptUrl)
            ->line(__('If you did not expect this email, you can ignore it.'));
    }
}
