<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TenantMembershipStatus;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantMembership>
 */
final class TenantMembershipFactory extends Factory
{
    protected $model = TenantMembership::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'job_title' => fake()->jobTitle(),
            'status' => TenantMembershipStatus::Active,
            'invited_at' => now(),
            'joined_at' => now(),
            'last_accessed_at' => now(),
        ];
    }

    public function invited(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantMembershipStatus::Invited,
            'joined_at' => null,
        ]);
    }
}
