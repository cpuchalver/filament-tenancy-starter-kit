<?php

use App\Models\TeamInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class);

it('knows whether it has expired', function () {
    expect(TeamInvitation::factory()->create()->isExpired())->toBeFalse()
        ->and(TeamInvitation::factory()->expired()->create()->isExpired())->toBeTrue();
});

it('prunes expired invitations and keeps pending ones', function () {
    $pending = TeamInvitation::factory()->create();
    $expired = TeamInvitation::factory()->expired()->create();

    artisan('model:prune', ['--model' => [TeamInvitation::class]])->assertSuccessful();

    expect($pending->fresh())->not->toBeNull()
        ->and($expired->fresh())->toBeNull();
});

it('prunes models every day', function () {
    artisan('schedule:list')
        ->expectsOutputToContain('model:prune')
        ->assertSuccessful();
});
