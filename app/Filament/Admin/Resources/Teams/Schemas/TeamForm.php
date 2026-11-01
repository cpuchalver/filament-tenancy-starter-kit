<?php

namespace App\Filament\Admin\Resources\Teams\Schemas;

use App\Filament\Schemas\TeamFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ...TeamFields::components(),
                Select::make('owner_id')
                    ->label(__('Owner'))
                    ->relationship('owner', 'name')
                    ->searchable(['name', 'email'])
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('Email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique('users', 'email'),
                        TextInput::make('password')
                            ->label(__('Password'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule(Password::default()),
                    ]),
            ]);
    }
}
