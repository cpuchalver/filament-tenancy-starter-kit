<?php

namespace App\Filament\App\Pages\Tenancy;

use App\Filament\App\Schemas\TeamForm;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;

class EditTeamProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return __('Team profile');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(TeamForm::components());
    }

    protected function getRedirectUrl(): ?string
    {
        if (! $this->tenant->wasChanged('slug')) {
            return null;
        }

        return static::getUrl(tenant: $this->tenant);
    }
}
