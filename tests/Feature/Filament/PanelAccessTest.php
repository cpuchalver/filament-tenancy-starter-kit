<?php

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('lets administrators open the admin panel', function () {
    actingAs(User::factory()->admin()->create());

    get(Dashboard::getUrl(panel: 'admin'))->assertOk();
});

it('hides the admin panel from other users', function () {
    actingAs(User::factory()->create());

    get(Dashboard::getUrl(panel: 'admin'))->assertNotFound();
});

it('sends users without a team to the team registration', function () {
    actingAs(User::factory()->create());

    get('/app')->assertRedirect(Filament::getPanel('app')->getTenantRegistrationUrl());
});
