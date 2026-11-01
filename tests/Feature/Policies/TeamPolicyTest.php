<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets the owner view, update and delete the team', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->ownedBy($owner)->create();

    expect($owner->can('view', $team))->toBeTrue()
        ->and($owner->can('update', $team))->toBeTrue()
        ->and($owner->can('delete', $team))->toBeTrue();
});

it('lets a member view the team but not update or delete it', function () {
    $team = Team::factory()->create();
    $member = User::factory()->create();
    $team->users()->attach($member);

    expect($member->can('view', $team))->toBeTrue()
        ->and($member->can('update', $team))->toBeFalse()
        ->and($member->can('delete', $team))->toBeFalse();
});

it('does not let an outsider view the team', function () {
    $team = Team::factory()->create();
    $outsider = User::factory()->create();

    expect($outsider->can('view', $team))->toBeFalse();
});

it('lets any user create a team', function () {
    expect(User::factory()->create()->can('create', Team::class))->toBeTrue();
});
