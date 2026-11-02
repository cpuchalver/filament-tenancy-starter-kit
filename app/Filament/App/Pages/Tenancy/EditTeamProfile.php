<?php

namespace App\Filament\App\Pages\Tenancy;

use App\Filament\Schemas\TeamFields;
use Filament\Notifications\Notification;
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
            ->components(TeamFields::components());
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title(__('Team saved'))
            ->success();
    }

    protected function getRedirectUrl(): ?string
    {
        if (! $this->tenant->wasChanged('slug')) {
            return null;
        }

        return static::getUrl(tenant: $this->tenant);
    }
}
