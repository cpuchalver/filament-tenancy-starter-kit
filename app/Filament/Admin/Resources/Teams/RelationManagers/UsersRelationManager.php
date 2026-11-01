<?php

namespace App\Filament\Admin\Resources\Teams\RelationManagers;

use App\Models\Team;
use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * @property Team $ownerRecord
 */
class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Members';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->badge(fn (User $record): bool => $this->ownerRecord->isOwnedBy($record))
                    ->color(fn (User $record): ?string => $this->ownerRecord->isOwnedBy($record) ? 'primary' : null),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable(),
                TextColumn::make('pivot.created_at')
                    ->label(__('Joined at'))
                    ->date(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordSelectSearchColumns(['name', 'email']),
            ])
            ->recordActions([
                DetachAction::make()
                    ->hidden(fn (User $record): bool => $this->ownerRecord->isOwnedBy($record)),
            ]);
    }
}
