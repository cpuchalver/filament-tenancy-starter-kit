<?php

use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can access a team it belongs to', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);

    expect($user->canAccessTenant($team))->toBeTrue();
});

it('cannot access a team it does not belong to', function () {
    $user = User::factory()->create();
    $user->teams()->attach(Team::factory()->create());
    $otherTeam = Team::factory()->create();

    expect($user->canAccessTenant($otherTeam))->toBeFalse();
});

it('lists only the teams it belongs to as tenants', function () {
    $user = User::factory()->create();
    $teams = Team::factory()->count(2)->create();
    $user->teams()->attach($teams);
    Team::factory()->create();

    $tenants = $user->getTenants(Filament::getDefaultPanel());

    expect($tenants->modelKeys())->toEqualCanonicalizing($teams->modelKeys());
});
