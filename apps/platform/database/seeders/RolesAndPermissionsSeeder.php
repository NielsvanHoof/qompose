<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }
    }
}
