<?php

use App\Models\Team;
use App\Models\User;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('opens the dashboard of a team the user belongs to', function () {
    $user = User::factory()->create();
    $team = Team::factory()->ownedBy($user)->create();

    actingAs($user);

    get(Dashboard::getUrl(tenant: $team))->assertOk();
});

it('hides teams the user does not belong to', function () {
    $user = User::factory()->create();
    Team::factory()->ownedBy($user)->create();
    $otherTeam = Team::factory()->create();

    actingAs($user);

    get(Dashboard::getUrl(tenant: $otherTeam))->assertNotFound();
});
