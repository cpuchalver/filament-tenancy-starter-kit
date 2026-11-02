<?php

namespace App\Filament\App\Pages\Tenancy;

use App\Filament\Schemas\TeamFields;
use App\Models\Team;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class RegisterTeam extends RegisterTenant
{
    public static function getLabel(): string
    {
        return __('Register team');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(TeamFields::components());
    }

    protected function handleRegistration(array $data): Team
    {
        $team = new Team($data);
        $team->assignOwner(Auth::user());

        return $team;
    }

    protected function afterRegister(): void
    {
        Notification::make()
            ->title(__('Team created'))
            ->body(__('You own :team. Invite people from the Members page.', ['team' => $this->tenant->name]))
            ->success()
            ->send();
    }
}
