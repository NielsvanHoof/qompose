<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ClientAccessGrant;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClientAccessGrant>
 */
final class ClientAccessGrantFactory extends Factory
{
    protected $model = ClientAccessGrant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dossier_id' => Dossier::factory(),
            'token' => ClientAccessGrant::hashToken(Str::random(64)),
            'expires_at' => now()->addDays(7),
            'created_by' => User::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ClientAccessGrant $grant): void {
            if ($grant->tenant_id !== null) {
                return;
            }

            $dossier = Dossier::query()
                ->withoutGlobalScopes()
                ->find($grant->dossier_id);

            if ($dossier instanceof Dossier) {
                $grant->tenant_id = $dossier->tenant_id;
            }
        });
    }
}
