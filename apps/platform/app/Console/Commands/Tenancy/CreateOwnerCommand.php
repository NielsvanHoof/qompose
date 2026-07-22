<?php

declare(strict_types=1);

namespace App\Console\Commands\Tenancy;

use App\Actions\Accounts\CreateNewUser;
use App\Actions\Tenancy\ProvisionTenantAction;
use App\Enums\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

use function is_string;

/**
 * Engineering bootstrap: create a verified owner and their first workspace.
 * After this, workspace owners invite colleagues via Members.
 */
final class CreateOwnerCommand extends Command
{
    protected $signature = 'tenancy:create-owner
                            {name : Full name of the owner}
                            {email : Email address used to sign in}
                            {--firm= : Firm / workspace name}
                            {--password= : Plain-text password (random if omitted)}';

    protected $description = 'Create a verified workspace owner and provision their firm';

    public function handle(
        CreateNewUser $createNewUser,
        ProvisionTenantAction $provisionTenant,
    ): int {
        $name = mb_trim($this->argument('name'));
        $email = mb_strtolower(mb_trim($this->argument('email')));

        $firmOption = $this->option('firm');
        $firmName = mb_trim(
            (is_string($firmOption) && $firmOption !== '')
                ? $firmOption
                : "{$name}'s firm",
        );

        $passwordOption = $this->option('password');
        $password = (is_string($passwordOption) && $passwordOption !== '')
            ? $passwordOption
            : Str::password(20);

        try {
            Validator::make(
                [
                    'name' => $name,
                    'email' => $email,
                    'firm' => $firmName,
                    'password' => $password,
                    'password_confirmation' => $password,
                ],
                [
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                    'firm' => ['required', 'string', 'max:255'],
                    'password' => ['required', 'string', Password::defaults(), 'confirmed'],
                ],
            )->validate();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        // Global permissions must exist before tenant roles are seeded.
        $this->callSilent('db:seed', [
            '--class' => RolesAndPermissionsSeeder::class,
            '--force' => true,
        ]);

        $user = $createNewUser->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $tenant = $provisionTenant->handle(
            name: $firmName,
            owner: $user,
            ownerRole: Role::Owner,
        );

        $this->info('Owner and workspace created.');
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $user->name],
                ['Email', $user->email],
                ['Password', $password],
                ['Firm', $tenant->name],
                ['Workspace slug', $tenant->slug],
                ['Role', Role::Owner->value],
            ],
        );

        return self::SUCCESS;
    }
}
