<?php

namespace App\Filament\Admin\Resources\Teams\Tables;

use App\Models\Team;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->searchable()
                    ->color('gray'),
                TextColumn::make('owner.name')
                    ->label(__('Owner'))
                    ->description(fn (Team $record): string => $record->owner->email)
                    ->searchable(['name', 'email']),
                TextColumn::make('users_count')
                    ->label(__('Members'))
                    ->counts('users')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Created at'))
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotificationTitle(__('Teams deleted')),
                ]),
            ]);
    }
}
