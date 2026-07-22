<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Models\Dossier;

use function array_key_exists;

final class UpdateDossierAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Dossier $dossier, array $attributes): void
    {
        if (! array_key_exists('reminder_interval_days', $attributes)) {
            $dossier->update($attributes);

            return;
        }

        $reminderIntervalDays = isset($attributes['reminder_interval_days'])
            ? (int) $attributes['reminder_interval_days']
            : null;
        $attributes['reminder_interval_days'] = $reminderIntervalDays;
        $shouldUpdateNextReminder = false;
        $nextReminderAt = null;

        if ($reminderIntervalDays === null) {
            $shouldUpdateNextReminder = true;
        } elseif (
            $dossier->reminder_interval_days !== $reminderIntervalDays
            && (
                $dossier->last_client_message_sent_at !== null
                || $dossier->next_reminder_at !== null
            )
        ) {
            $shouldUpdateNextReminder = true;
            $nextReminderAt = now()->addDays($reminderIntervalDays);
        }

        $dossier->fill($attributes);

        if ($shouldUpdateNextReminder) {
            $dossier->forceFill(['next_reminder_at' => $nextReminderAt]);
        }

        $dossier->save();
    }
}
