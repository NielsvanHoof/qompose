<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\TenantMembershipStatus;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tenancy create-owner provisions a verified owner and firm', function () {
    $this->artisan('tenancy:create-owner', [
        'name' => 'Ada Owner',
        'email' => 'ada@example.com',
        '--firm' => 'Ada Accountants',
        '--password' => 'password',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'ada@example.com')->firstOrFail();
    $tenant = Tenant::query()->where('slug', 'ada-accountants')->firstOrFail();

    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->name)->toBe('Ada Owner');

    $membership = TenantMembership::query()
        ->where('tenant_id', $tenant->id)
        ->where('user_id', $user->id)
        ->firstOrFail();

    expect($membership->status)->toBe(TenantMembershipStatus::Active);

    actingInWorkspace($tenant);
    expect($user->fresh()->hasRole(Role::Owner->value))->toBeTrue();
});

test('tenancy create-owner rejects duplicate emails', function () {
    User::factory()->create(['email' => 'ada@example.com']);

    $this->artisan('tenancy:create-owner', [
        'name' => 'Ada Owner',
        'email' => 'ada@example.com',
        '--firm' => 'Ada Accountants',
        '--password' => 'password',
    ])->assertFailed();

    expect(Tenant::query()->count())->toBe(0);
});
