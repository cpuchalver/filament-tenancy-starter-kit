<?php

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->admin = User::factory()->admin()->create();
    actingAs($this->admin);
});

it('lists every user', function () {
    $users = User::factory()->count(3)->create();

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords($users);
});

it('filters administrators', function () {
    $member = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->filterTable('is_admin', true)
        ->assertCanSeeTableRecords([$this->admin])
        ->assertCanNotSeeTableRecords([$member]);
});

it('creates an administrator', function () {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'a-strong-password',
            'is_admin' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::firstWhere('email', 'jane@example.com');

    expect($user->is_admin)->toBeTrue()
        ->and(Hash::check('a-strong-password', $user->password))->toBeTrue();
});

it('validates the user data', function (array $data, array $errors) {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'a-strong-password',
            ...$data,
        ])
        ->call('create')
        ->assertHasFormErrors($errors);
})->with([
    'name is required' => [['name' => null], ['name' => 'required']],
    'email is valid' => [['email' => 'not-an-email'], ['email' => 'email']],
    'email is unique' => [['email' => 'taken@example.com'], ['email' => 'unique']],
    'password is required on creation' => [['password' => null], ['password' => 'required']],
]);

it('grants and revokes the administrator right', function () {
    $user = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['is_admin' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh()->is_admin)->toBeTrue();

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['is_admin' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh()->is_admin)->toBeFalse();
});

it('keeps the current password when the field is left empty', function () {
    $user = User::factory()->create(['password' => 'the-original-password']);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['name' => 'Renamed', 'password' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->name)->toBe('Renamed')
        ->and(Hash::check('the-original-password', $user->password))->toBeTrue();
});

it('does not let administrators revoke their own right', function () {
    Livewire::test(EditUser::class, ['record' => $this->admin->getRouteKey()])
        ->assertFormFieldDisabled('is_admin')
        ->fillForm(['is_admin' => false])
        ->call('save');

    expect($this->admin->fresh()->is_admin)->toBeTrue();
});

it('deletes a user who owns no team', function () {
    $user = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make(DeleteAction::class)->table($user));

    assertModelMissing($user);
});

it('does not delete team owners or the current administrator', function () {
    $owner = User::factory()->create();
    Team::factory()->ownedBy($owner)->create();

    Livewire::test(ListUsers::class)
        ->assertActionHidden(TestAction::make(DeleteAction::class)->table($owner))
        ->assertActionHidden(TestAction::make(DeleteAction::class)->table($this->admin));

    Livewire::test(EditUser::class, ['record' => $owner->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);
});

it('hides user management from non administrators', function () {
    actingAs(User::factory()->create());

    get(UserResource::getUrl('index', panel: 'admin'))->assertNotFound();
});
