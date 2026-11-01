<?php

use App\Filament\App\Pages\AcceptInvitation;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create(['name' => 'Acme']);
    $this->invitation = TeamInvitation::factory()
        ->for($this->team)
        ->withToken('valid-token')
        ->create(['email' => 'jane@example.com']);
});

it('links the invitation email to the acceptance page', function () {
    $mail = (new TeamInvitationNotification($this->invitation, 'valid-token'))->toMail(new stdClass);

    expect($mail->actionUrl)->toBe(AcceptInvitation::getUrlForToken('valid-token'));
});

it('creates the account of a new person and adds them to the team', function () {
    Livewire::test(AcceptInvitation::class, ['token' => 'valid-token'])
        ->fillForm([
            'name' => 'Jane Doe',
            'password' => 'a-strong-password',
            'passwordConfirmation' => 'a-strong-password',
        ])
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertRedirect(Dashboard::getUrl(panel: 'app', tenant: $this->team));

    $user = User::firstWhere('email', 'jane@example.com');

    expect($user->name)->toBe('Jane Doe')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($this->team->users()->whereKey($user)->exists())->toBeTrue()
        ->and($this->invitation->fresh())->toBeNull();

    assertAuthenticatedAs($user);
});

it('requires a confirmed password to create the account', function () {
    Livewire::test(AcceptInvitation::class, ['token' => 'valid-token'])
        ->fillForm([
            'name' => 'Jane Doe',
            'password' => 'a-strong-password',
            'passwordConfirmation' => 'something-else',
        ])
        ->call('register')
        ->assertHasFormErrors(['password' => 'same']);

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
    assertGuest();
});

it('asks people who already have an account to sign in first', function () {
    User::factory()->create(['email' => 'Jane@Example.com']);

    get(AcceptInvitation::getUrlForToken('valid-token'))
        ->assertOk()
        ->assertSee('Sign in to join Acme');

    expect(session('url.intended'))->toBe(AcceptInvitation::getUrlForToken('valid-token'));

    Livewire::test(AcceptInvitation::class, ['token' => 'valid-token'])
        ->call('register');

    expect(User::whereRaw('lower(email) = ?', ['jane@example.com'])->count())->toBe(1);
});

it('lets the invited person join once signed in', function () {
    $user = User::factory()->unverified()->create(['email' => 'jane@example.com']);
    actingAs($user);

    Livewire::test(AcceptInvitation::class, ['token' => 'valid-token'])
        ->call('join')
        ->assertRedirect(Dashboard::getUrl(panel: 'app', tenant: $this->team));

    expect($this->team->users()->whereKey($user)->exists())->toBeTrue()
        ->and($user->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and($this->invitation->fresh())->toBeNull();
});

it('does not let another account use the invitation', function () {
    $other = User::factory()->create();
    actingAs($other);

    get(AcceptInvitation::getUrlForToken('valid-token'))
        ->assertOk()
        ->assertSee('This invitation was sent to jane@example.com.');

    Livewire::test(AcceptInvitation::class, ['token' => 'valid-token'])
        ->call('join');

    expect($this->team->users()->whereKey($other)->exists())->toBeFalse()
        ->and($this->invitation->fresh())->not->toBeNull();
});

it('explains when an invitation link is unknown', function () {
    get(AcceptInvitation::getUrlForToken('unknown-token'))
        ->assertOk()
        ->assertSee('This invitation link is invalid.');
});

it('explains when an invitation has expired', function () {
    TeamInvitation::factory()->for($this->team)->withToken('expired-token')->expired()->create();

    get(AcceptInvitation::getUrlForToken('expired-token'))
        ->assertOk()
        ->assertSee('This invitation to join Acme expired on');
});

it('does not create an account from an unusable invitation', function (string $token) {
    TeamInvitation::factory()->for($this->team)->withToken('expired-token')->expired()->create();

    Livewire::test(AcceptInvitation::class, ['token' => $token])
        ->fillForm([
            'name' => 'Jane Doe',
            'password' => 'a-strong-password',
            'passwordConfirmation' => 'a-strong-password',
        ])
        ->call('register');

    expect(User::count())->toBe(1);
})->with(['unknown-token', 'expired-token']);

it('does not let a signed-in invitee join with an expired invitation', function () {
    $this->invitation->update(['expires_at' => now()->subMinute()]);
    $user = User::factory()->create(['email' => 'jane@example.com']);
    actingAs($user);

    Livewire::test(AcceptInvitation::class, ['token' => 'valid-token'])
        ->call('join')
        ->assertNoRedirect();

    expect($this->team->users()->whereKey($user)->exists())->toBeFalse();
});
