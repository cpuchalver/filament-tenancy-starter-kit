<?php

use App\Filament\Admin\Resources\Teams\Pages\CreateTeam;
use App\Filament\Admin\Resources\Teams\Pages\EditTeam;
use App\Filament\Admin\Resources\Teams\Pages\ListTeams;
use App\Filament\Admin\Resources\Teams\RelationManagers\UsersRelationManager;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\App\Pages\AcceptInvitation;
use App\Filament\App\Pages\Members;
use App\Filament\App\Pages\Tenancy\EditTeamProfile;
use App\Filament\App\Pages\Tenancy\RegisterTeam;
use App\Filament\App\Widgets\PendingInvitations;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFake;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function toast(string $title, ?string $body = null): Notification
{
    return Notification::make()
        ->title($title)
        ->body($body)
        ->success();
}

describe('team panel', function () {
    beforeEach(function () {
        NotificationFake::fake();

        $this->owner = User::factory()->create();
        $this->team = Team::factory()->ownedBy($this->owner)->create(['name' => 'Acme']);
        actingAs($this->owner);
        Filament::setTenant($this->team, isQuiet: true);
    });

    it('confirms an invitation', function () {
        Livewire::test(Members::class)
            ->callAction(TestAction::make('invite')->table(), ['email' => 'jane@example.com'])
            ->assertNotified(toast('Invitation sent', 'jane@example.com will receive a link valid for 7 days.'));
    });

    it('confirms the removal of a member', function () {
        $member = User::factory()->create(['name' => 'Jane Doe']);
        $this->team->users()->attach($member);

        Livewire::test(Members::class)
            ->callAction(TestAction::make('remove')->table($member))
            ->assertNotified(toast('Member removed', 'Jane Doe no longer has access to Acme.'));
    });

    it('confirms a resent invitation', function () {
        $invitation = TeamInvitation::factory()->for($this->team)->create(['email' => 'jane@example.com']);

        Livewire::test(PendingInvitations::class)
            ->callAction(TestAction::make('resend')->table($invitation))
            ->assertNotified(toast('Invitation resent', 'A new link was sent to jane@example.com. The previous link no longer works.'));
    });

    it('confirms a revoked invitation', function () {
        $invitation = TeamInvitation::factory()->for($this->team)->create(['email' => 'jane@example.com']);

        Livewire::test(PendingInvitations::class)
            ->callAction(TestAction::make('revoke')->table($invitation))
            ->assertNotified(toast('Invitation revoked', 'The link sent to jane@example.com no longer works.'));
    });

    it('confirms the team profile was saved', function () {
        Livewire::test(EditTeamProfile::class)
            ->fillForm(['name' => 'Acme Industries'])
            ->call('save')
            ->assertNotified(toast('Team saved'));
    });

    it('confirms the team was created', function () {
        Livewire::test(RegisterTeam::class)
            ->fillForm(['name' => 'Globex', 'slug' => 'globex'])
            ->call('register')
            ->assertNotified(toast('Team created', 'You own Globex. Invite people from the Members page.'));
    });
});

it('welcomes people who accept an invitation', function () {
    $team = Team::factory()->create(['name' => 'Acme']);
    TeamInvitation::factory()->for($team)->withToken('valid-token')->create(['email' => 'jane@example.com']);

    Livewire::test(AcceptInvitation::class, ['token' => 'valid-token'])
        ->fillForm([
            'name' => 'Jane Doe',
            'password' => 'a-strong-password',
            'passwordConfirmation' => 'a-strong-password',
        ])
        ->call('register')
        ->assertNotified(toast('Welcome to Acme', 'You are now a member of the team.'));
});

describe('admin panel', function () {
    beforeEach(function () {
        Filament::setCurrentPanel('admin');

        actingAs(User::factory()->admin()->create());
    });

    it('confirms a team was created', function () {
        $owner = User::factory()->create(['name' => 'Jane Doe']);

        Livewire::test(CreateTeam::class)
            ->fillForm(['name' => 'Acme', 'slug' => 'acme', 'owner_id' => $owner->id])
            ->call('create')
            ->assertNotified(toast('Team created', 'Acme is owned by Jane Doe.'));
    });

    it('confirms a team was saved', function () {
        $team = Team::factory()->create();

        Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])
            ->fillForm(['name' => 'Renamed'])
            ->call('save')
            ->assertNotified(toast('Team saved'));
    });

    it('confirms a team was deleted', function () {
        $team = Team::factory()->create(['name' => 'Acme']);

        Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified(toast('Team deleted', 'Acme and its memberships were deleted.'));
    });

    it('confirms teams were deleted in bulk', function () {
        $teams = Team::factory()->count(2)->create();

        Livewire::test(ListTeams::class)
            ->selectTableRecords($teams)
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
            ->assertNotified('Teams deleted');
    });

    it('confirms a member was added and removed', function () {
        $team = Team::factory()->create(['name' => 'Acme']);
        $user = User::factory()->create(['name' => 'Jane Doe']);

        $relationManager = Livewire::test(UsersRelationManager::class, ['ownerRecord' => $team, 'pageClass' => EditTeam::class]);

        $relationManager
            ->callAction(TestAction::make(AttachAction::class)->table(), ['recordId' => $user->id])
            ->assertNotified(toast('Member added', 'Jane Doe can now access Acme.'));

        $relationManager
            ->callAction(TestAction::make(DetachAction::class)->table($user))
            ->assertNotified(toast('Member removed', 'Jane Doe no longer has access to Acme.'));
    });

    it('confirms a user was created', function () {
        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'password' => 'a-strong-password'])
            ->call('create')
            ->assertNotified(toast('User created', 'jane@example.com will be asked to verify their address at first sign-in.'));
    });

    it('confirms a user was saved', function () {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['name' => 'Renamed'])
            ->call('save')
            ->assertNotified(toast('User saved'));
    });

    it('confirms a user was deleted from the list and from the edit page', function () {
        [$listed, $edited] = User::factory()->count(2)->sequence(['email' => 'one@example.com'], ['email' => 'two@example.com'])->create();

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($listed))
            ->assertNotified(toast('User deleted', 'one@example.com no longer has an account.'));

        Livewire::test(EditUser::class, ['record' => $edited->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified(toast('User deleted', 'two@example.com no longer has an account.'));
    });
});
