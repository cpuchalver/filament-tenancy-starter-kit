<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\travelTo;

uses(RefreshDatabase::class);

it('lists only its members as users', function () {
    $team = Team::factory()->create();
    $members = User::factory()->count(2)->create();
    $team->users()->attach($members);
    User::factory()->create();

    $users = $team->users;

    expect($users->modelKeys())->toEqualCanonicalizing($members->modelKeys());
});

it('records when a user joined the team', function () {
    travelTo('2026-01-15 10:00:00');
    $team = Team::factory()->create();
    $team->users()->attach(User::factory()->create());

    $membership = $team->users()->first()->pivot;

    expect($membership->created_at->toDateTimeString())->toBe('2026-01-15 10:00:00');
});

it('rejects a slug already used by another team', function () {
    Team::factory()->create(['slug' => 'acme']);

    expect(fn () => Team::factory()->create(['slug' => 'acme']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('rejects adding the same user twice', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);

    expect(fn () => $team->users()->attach($user))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('removes its memberships but keeps the users when deleted', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);

    $team->delete();

    assertDatabaseMissing('team_user', ['team_id' => $team->id]);
    assertModelExists($user);
});
