<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TeamInvitation>
 */
class TeamInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'email' => fake()->unique()->safeEmail(),
            'token_hash' => TeamInvitation::hashToken(Str::random(64)),
            'expires_at' => now()->addDays(TeamInvitation::ValidForDays),
        ];
    }

    public function withToken(string $token): static
    {
        return $this->state(fn (array $attributes) => [
            'token_hash' => TeamInvitation::hashToken($token),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
