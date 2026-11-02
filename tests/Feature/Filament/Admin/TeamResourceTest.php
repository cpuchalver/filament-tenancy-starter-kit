<?php

use App\Filament\Admin\Resources\Teams\Pages\CreateTeam;
use App\Filament\Admin\Resources\Teams\Pages\EditTeam;
use App\Filament\Admin\Resources\Teams\Pages\ListTeams;
use App\Filament\Admin\Resources\Teams\RelationManagers\UsersRelationManager;
use App\Filament\Admin\Resources\Teams\TeamResource;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

it('lists every team', function () {
    actingAs(User::factory()->admin()->create());
    $teams = Team::factory()->count(3)->create();

    Livewire::test(ListTeams::class)
        ->assertCanSeeTableRecords($teams);
});

it('creates a team whose owner is also a member', function () {
    actingAs(User::factory()->admin()->create());
    $owner = User::factory()->create();

    Livewire::test(CreateTeam::class)
        ->fillForm([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'owner_id' => $owner->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $team = Team::firstWhere('slug', 'acme-corp');

    expect($team->isOwnedBy($owner))->toBeTrue()
        ->and($team->users->modelKeys())->toBe([$owner->id]);
});

it('creates a new user as the owner of a new team', function () {
    actingAs(User::factory()->admin()->create());

    Livewire::test(CreateTeam::class)
        ->fillForm([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ])
        ->callAction(TestAction::make('createOption')->schemaComponent('owner_id'), data: [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'a-strong-password',
        ])
        ->assertHasNoFormComponentActionErrors()
        ->call('create')
        ->assertHasNoFormErrors();

    $owner = User::firstWhere('email', 'jane@example.com');
    $team = Team::firstWhere('slug', 'acme-corp');

    expect($owner)->not->toBeNull()
        ->and(Hash::check('a-strong-password', $owner->password))->toBeTrue()
        ->and($team->isOwnedBy($owner))->toBeTrue()
        ->and($team->users->modelKeys())->toBe([$owner->id]);
});

it('rejects an email already used when creating the owner', function () {
    actingAs(User::factory()->admin()->create());
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test(CreateTeam::class)
        ->callAction(TestAction::make('createOption')->schemaComponent('owner_id'), data: [
            'name' => 'Jane Doe',
            'email' => 'taken@example.com',
            'password' => 'a-strong-password',
        ])
        ->assertHasActionErrors(['email' => 'unique']);
});

it('requires an owner and a valid slug', function () {
    actingAs(User::factory()->admin()->create());

    Livewire::test(CreateTeam::class)
        ->fillForm([
            'name' => 'Acme Corp',
            'slug' => 'new',
            'owner_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'slug' => 'not_in',
            'owner_id' => 'required',
        ]);
});

it('edits a team the administrator does not belong to', function () {
    actingAs(User::factory()->admin()->create());
    $team = Team::factory()->create();

    Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])
        ->fillForm(['name' => 'Renamed'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($team->fresh()->name)->toBe('Renamed');
});

it('keeps the former owner as a member when transferring ownership', function () {
    actingAs(User::factory()->admin()->create());
    $formerOwner = User::factory()->create();
    $team = Team::factory()->ownedBy($formerOwner)->create();
    $newOwner = User::factory()->create();

    Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])
        ->fillForm(['owner_id' => $newOwner->id])
        ->call('save')
        ->assertHasNoFormErrors();

    $team->refresh();

    expect($team->isOwnedBy($newOwner))->toBeTrue()
        ->and($team->users->modelKeys())->toEqualCanonicalizing([$formerOwner->id, $newOwner->id]);
});

it('attaches a member to a team', function () {
    actingAs(User::factory()->admin()->create());
    $team = Team::factory()->create();
    $user = User::factory()->create();

    Livewire::test(UsersRelationManager::class, [
        'ownerRecord' => $team,
        'pageClass' => EditTeam::class,
    ])
        ->callAction(TestAction::make(AttachAction::class)->table(), ['recordId' => $user->id]);

    expect($team->users()->whereKey($user)->exists())->toBeTrue();
});

it('does not let the owner be detached', function () {
    actingAs(User::factory()->admin()->create());
    $owner = User::factory()->create();
    $team = Team::factory()->ownedBy($owner)->create();
    $member = User::factory()->create();
    $team->users()->attach($member);

    Livewire::test(UsersRelationManager::class, [
        'ownerRecord' => $team,
        'pageClass' => EditTeam::class,
    ])
        ->assertActionHidden(TestAction::make('detach')->table($owner))
        ->assertActionVisible(TestAction::make('detach')->table($member));
});

it('hides the team management from non administrators', function () {
    actingAs(User::factory()->create());

    get(TeamResource::getUrl('index', panel: 'admin'))->assertNotFound();
});
