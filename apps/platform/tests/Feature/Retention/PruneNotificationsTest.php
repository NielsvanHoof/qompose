<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\Portal\ClientQuestionnaireCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'retention.notifications_days' => 90,
    ]);
});

test('old database notifications are pruned while recent notifications remain', function (): void {
    $user = User::factory()->create();

    $oldNotificationId = (string) \Illuminate\Support\Str::uuid();
    $recentNotificationId = (string) \Illuminate\Support\Str::uuid();

    DB::table('notifications')->insert([
        [
            'id' => $oldNotificationId,
            'type' => ClientQuestionnaireCompletedNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['message' => 'Old notification']),
            'read_at' => now()->subDays(100),
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100),
        ],
        [
            'id' => $recentNotificationId,
            'type' => ClientQuestionnaireCompletedNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['message' => 'Recent notification']),
            'read_at' => null,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ],
    ]);

    $this->artisan('retention:prune-notifications')
        ->assertSuccessful();

    expect(DB::table('notifications')->where('id', $oldNotificationId)->exists())->toBeFalse()
        ->and(DB::table('notifications')->where('id', $recentNotificationId)->exists())->toBeTrue();
});
