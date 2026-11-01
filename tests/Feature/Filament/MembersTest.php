<?php

use App\Filament\App\Pages\Members;
use App\Filament\App\Widgets\PendingInvitations;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();

    $this->owner = User::factory()->create();
    $this->team = Team::factory()->ownedBy($this->owner)->create();
    $this->member = User::factory()->create();
    $this->team->users()->attach($this->member);

    Filament::setTenant($this->team, isQuiet: true);
});

it('lists the members of the current team only', function () {
    actingAs($this->owner);
    $outsider = Team::factory()->create()->owner;

    Livewire::test(Members::class)
        ->assertCanSeeTableRecords([$this->owner, $this->member])
        ->assertCanNotSeeTableRecords([$outsider]);
});

it('emails an invitation whose link identifies it', function () {
    actingAs($this->owner);

    Livewire::test(Members::class)
        ->callAction(TestAction::make('invite')->table(), ['email' => 'Jane@Example.com'])
        ->assertHasNoActionErrors();

    $invitation = $this->team->invitations()->sole();

    expect($invitation->email)->toBe('jane@example.com')
        ->and($invitation->invitedBy->is($this->owner))->toBeTrue();

    Notification::assertSentOnDemand(
        TeamInvitationNotification::class,
        fn (TeamInvitationNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'jane@example.com'
            && TeamInvitation::findByToken($notification->token)?->is($invitation),
    );
});

it('does not invite someone who is already a member', function () {
    actingAs($this->owner);

    Livewire::test(Members::class)
        ->callAction(TestAction::make('invite')->table(), ['email' => strtoupper($this->member->email)])
        ->assertHasActionErrors(['email']);

    expect($this->team->invitations()->exists())->toBeFalse();
    Notification::assertNothingSent();
});

it('renews the link when inviting the same address again', function () {
    $tokens = [];
    Notification::fake();

    $this->team->invite('jane@example.com', $this->owner);
    $this->team->invite('jane@example.com', $this->owner);

    Notification::assertSentOnDemand(TeamInvitationNotification::class, function (TeamInvitationNotification $notification) use (&$tokens): bool {
        $tokens[] = $notification->token;

        return true;
    });

    expect($this->team->invitations()->count())->toBe(1)
        ->and(TeamInvitation::findByToken($tokens[0]))->toBeNull()
        ->and(TeamInvitation::findByToken($tokens[1]))->not->toBeNull();
});

it('removes a member but never the owner', function () {
    actingAs($this->owner);

    Livewire::test(Members::class)
        ->assertActionHidden(TestAction::make('remove')->table($this->owner))
        ->callAction(TestAction::make('remove')->table($this->member));

    expect($this->team->users()->whereKey($this->member)->exists())->toBeFalse();
});

it('hides the members page from members who do not own the team', function () {
    actingAs($this->member);

    get(Members::getUrl(tenant: $this->team))->assertNotFound();

    expect(Members::canAccess())->toBeFalse()
        ->and(PendingInvitations::canView())->toBeFalse();
});

it('shows the members page in the navigation of the owner', function () {
    actingAs($this->owner);

    get(Dashboard::getUrl(tenant: $this->team))->assertSee(Members::getUrl(tenant: $this->team));
});

it('leaves the members page out of the navigation of other members', function () {
    actingAs($this->member);

    get(Dashboard::getUrl(tenant: $this->team))->assertDontSee(Members::getUrl(tenant: $this->team));
});

it('shows only pending invitations of the current team', function () {
    actingAs($this->owner);
    $pending = TeamInvitation::factory()->for($this->team)->create();
    $expired = TeamInvitation::factory()->for($this->team)->expired()->create();
    $otherTeam = TeamInvitation::factory()->create();

    Livewire::test(PendingInvitations::class)
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$expired, $otherTeam]);
});

it('revokes an invitation', function () {
    actingAs($this->owner);
    $invitation = TeamInvitation::factory()->for($this->team)->create();

    Livewire::test(PendingInvitations::class)
        ->callAction(TestAction::make('revoke')->table($invitation));

    expect($invitation->fresh())->toBeNull();
});

it('resends an invitation with a new link', function () {
    actingAs($this->owner);
    $invitation = TeamInvitation::factory()->for($this->team)->withToken('old-token')->create();

    Livewire::test(PendingInvitations::class)
        ->callAction(TestAction::make('resend')->table($invitation));

    expect(TeamInvitation::findByToken('old-token'))->toBeNull();
    Notification::assertSentOnDemandTimes(TeamInvitationNotification::class, 1);
});
