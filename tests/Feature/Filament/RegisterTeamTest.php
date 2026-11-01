<?php

use App\Filament\App\Pages\Tenancy\RegisterTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('creates a team owned by the current user', function () {
    $user = User::factory()->create();
    actingAs($user);

    Livewire::test(RegisterTeam::class)
        ->fillForm([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $team = Team::firstWhere('slug', 'acme-corp');

    expect($team->name)->toBe('Acme Corp')
        ->and($team->isOwnedBy($user))->toBeTrue()
        ->and($team->users->modelKeys())->toBe([$user->id]);
});

it('fills the slug from the name', function () {
    actingAs(User::factory()->create());

    Livewire::test(RegisterTeam::class)
        ->fillForm(['name' => 'Acme Corp'])
        ->assertSchemaStateSet(['slug' => 'acme-corp']);
});

it('validates the team data', function (array $data, array $errors) {
    actingAs(User::factory()->create());
    Team::factory()->create(['slug' => 'taken']);

    Livewire::test(RegisterTeam::class)
        ->fillForm([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            ...$data,
        ])
        ->call('register')
        ->assertHasFormErrors($errors);

    expect(Team::where('name', 'Acme Corp')->exists())->toBeFalse();
})->with([
    'name is required' => [['name' => null], ['name' => 'required']],
    'slug is required' => [['slug' => null], ['slug' => 'required']],
    'slug is unique' => [['slug' => 'taken'], ['slug' => 'unique']],
    'slug is url safe' => [['slug' => 'acme corp/1'], ['slug' => 'alpha_dash']],
    'slug is not a reserved panel path' => [['slug' => 'new'], ['slug' => 'not_in']],
]);
