<?php

declare(strict_types=1);

use App\Actions\Tenancy\ProvisionTenant;
use App\Enums\Role as RoleEnum;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as RoleModel;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('tenant provisioning creates the complete tenant graph atomically', function () {
    $owner = User::factory()->create();

    $tenant = app(ProvisionTenant::class)('Acme Accountants', $owner);

    expect($tenant->slug)->toBe('acme-accountants')
        ->and(TenantMembership::query()
            ->whereBelongsTo($tenant)
            ->whereBelongsTo($owner)
            ->exists())->toBeTrue()
        ->and(RoleModel::query()
            ->where('tenant_id', $tenant->id)
            ->count())->toBe(count(RoleEnum::cases()));
});

test('tenant provisioning rolls back the complete tenant graph when a later step fails', function () {
    setPermissionsTeamId(123);

    $owner = User::factory()->create();
    $owner->delete();

    expect(fn () => app(ProvisionTenant::class)('Acme Accountants', $owner))
        ->toThrow(QueryException::class);

    expect(Tenant::query()->count())->toBe(0)
        ->and(TenantMembership::query()->count())->toBe(0)
        ->and(RoleModel::query()->whereNotNull('tenant_id')->count())->toBe(0)
        ->and(getPermissionsTeamId())->toBe(123);
});

test('tenant provisioning increments a slug that is already reserved', function () {
    Tenant::factory()->create([
        'name' => 'Existing firm',
        'slug' => 'acme-accountants',
    ]);

    $owner = User::factory()->create();

    $secondTenant = app(ProvisionTenant::class)('Acme Accountants', $owner);
    $thirdTenant = app(ProvisionTenant::class)('Acme Accountants', User::factory()->create());

    expect($secondTenant->slug)->toBe('acme-accountants-2')
        ->and($thirdTenant->slug)->toBe('acme-accountants-3');
});

test('tenant provisioning retries when a slug is claimed during insertion', function () {
    DB::unprepared(<<<'SQL'
        CREATE TRIGGER reserve_acme_slug
        BEFORE INSERT ON tenants
        WHEN NEW.slug = 'acme-accountants'
        BEGIN
            INSERT INTO tenants (name, slug, created_at, updated_at)
            VALUES ('Concurrent firm', 'acme-accountants', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
        END
        SQL);

    try {
        $tenant = app(ProvisionTenant::class)(
            'Acme Accountants',
            User::factory()->create(),
        );
    } finally {
        DB::unprepared('DROP TRIGGER IF EXISTS reserve_acme_slug');
    }

    expect($tenant->slug)->toBe('acme-accountants-2')
        ->and(Tenant::query()->count())->toBe(1);
});

test('firm onboarding uses the insertion-safe slug allocator', function () {
    Tenant::factory()->create([
        'name' => 'Existing firm',
        'slug' => 'acme-accountants',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('onboarding.firm.store'), ['name' => 'Acme Accountants'])
        ->assertRedirect(workspaceRoute('workspaces.clients.create', 'acme-accountants-2'));
});
