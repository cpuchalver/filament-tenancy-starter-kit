<?php

namespace App\Filament\Admin\Resources\Teams\RelationManagers;

use App\Models\Team;
use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Notifications\Notification;
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
                    ->recordSelectSearchColumns(['name', 'email'])
                    ->successNotification(fn (Notification $notification, array $data): Notification => $notification
                        ->title(__('Member added'))
                        ->body(__(':name can now access :team.', [
                            'name' => User::find($data['recordId'])?->name,
                            'team' => $this->ownerRecord->name,
                        ]))),
            ])
            ->recordActions([
                DetachAction::make()
                    ->hidden(fn (User $record): bool => $this->ownerRecord->isOwnedBy($record))
                    ->successNotification(fn (Notification $notification, User $record): Notification => $notification
                        ->title(__('Member removed'))
                        ->body(__(':name no longer has access to :team.', ['name' => $record->name, 'team' => $this->ownerRecord->name]))),
            ]);
    }
}
