<?php

namespace App\Filament\App\Pages\Tenancy;

use App\Filament\App\Schemas\TeamForm;
use App\Models\Team;
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
            ->components(TeamForm::components());
    }

    protected function handleRegistration(array $data): Team
    {
        $team = new Team($data);
        $team->owner()->associate(Auth::user());
        $team->save();

        $team->users()->attach(Auth::user());

        return $team;
    }
}
