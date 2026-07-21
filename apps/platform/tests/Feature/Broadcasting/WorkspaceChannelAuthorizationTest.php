<?php

declare(strict_types=1);

use App\Actions\Tenancy\ProvisionTenantAction;
use App\Enums\TenantMembershipStatus;
use App\Models\TenantMembership;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    // phpunit boots with BROADCAST_CONNECTION=null; channel auth needs a signing driver.
    // Switching the default broadcaster creates a fresh instance without routes/channels.php.
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'testing-key',
        'broadcasting.connections.reverb.secret' => 'testing-secret',
        'broadcasting.connections.reverb.app_id' => 'testing',
        'broadcasting.connections.reverb.options' => [
            'host' => 'localhost',
            'port' => 8080,
            'scheme' => 'http',
            'useTLS' => false,
        ],
    ]);

    require base_path('routes/channels.php');
});

test('tenant member can authorize the workspace private channel', function () {
    $owner = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);

    $this->actingAs($owner)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-workspaces.'.$tenant->slug,
            'socket_id' => '1234.5678',
        ])
        ->assertOk()
        ->assertJsonStructure(['auth']);
});

test('non-member cannot authorize the workspace private channel', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);

    $this->actingAs($outsider)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-workspaces.'.$tenant->slug,
            'socket_id' => '1234.5678',
        ])
        ->assertForbidden();
});

test('suspended member cannot authorize the workspace private channel', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $tenant = app(ProvisionTenantAction::class)->handle('Acme Accountants', $owner);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $member->id,
        'status' => TenantMembershipStatus::Suspended,
        'joined_at' => now(),
    ]);

    $this->actingAs($member)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-workspaces.'.$tenant->slug,
            'socket_id' => '1234.5678',
        ])
        ->assertForbidden();
});
