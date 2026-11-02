<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->admin()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);
        Team::factory()->ownedBy($user)->create([
            'name' => 'Demo Team',
            'slug' => 'demo-team',
        ]);
    }
}
