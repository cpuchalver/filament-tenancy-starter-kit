<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();
        $slug = Str::slug($name);

        return [
            'name' => $name,
            'slug' => $slug,
            'owner_id' => User::factory(),
        ];
    }

    public function ownedBy(User $owner): static
    {
        return $this
            ->for($owner, 'owner')
            ->afterCreating(fn (Team $team) => $team->users()->attach($owner));
    }
}
