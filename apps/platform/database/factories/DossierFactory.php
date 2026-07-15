<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DossierStatus;
use App\Models\Client;
use App\Models\Dossier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dossier>
 */
final class DossierFactory extends Factory
{
    protected $model = Dossier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'title' => fake()->sentence(3),
            'reference' => mb_strtoupper(fake()->unique()->bothify('DOS-####')),
            'status' => DossierStatus::Draft,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Dossier $dossier): void {
            if ($dossier->tenant_id !== null) {
                return;
            }

            $client = Client::query()
                ->withoutGlobalScopes()
                ->find($dossier->client_id);

            if ($client instanceof Client) {
                $dossier->tenant_id = $client->tenant_id;
            }
        });
    }
}
