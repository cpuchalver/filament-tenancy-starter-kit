<?php

use App\Filament\App\Pages\Tenancy\EditTeamProfile;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->team = Team::factory()->ownedBy($this->owner)->create(['slug' => 'acme']);

    actingAs($this->owner);
    Filament::setTenant($this->team);
});

it('lets the owner rename the team without leaving the page', function () {
    Livewire::test(EditTeamProfile::class)
        ->fillForm(['name' => 'Acme Industries'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNoRedirect();

    expect($this->team->fresh()->name)->toBe('Acme Industries');
});

it('redirects to the new url when the slug changes', function () {
    Livewire::test(EditTeamProfile::class)
        ->fillForm(['slug' => 'acme-industries'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(EditTeamProfile::getUrl(tenant: $this->team->fresh()));
});

it('rejects the slug of another team', function () {
    Team::factory()->create(['slug' => 'globex']);

    Livewire::test(EditTeamProfile::class)
        ->fillForm(['slug' => 'globex'])
        ->call('save')
        ->assertHasFormErrors(['slug' => 'unique']);

    expect($this->team->fresh()->slug)->toBe('acme');
});

it('hides the team profile from members who do not own the team', function () {
    $member = User::factory()->create();
    $this->team->users()->attach($member);

    actingAs($member);

    get(EditTeamProfile::getUrl(tenant: $this->team))->assertNotFound();
});
