<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable(),
                IconColumn::make('is_admin')
                    ->label(__('Administrator'))
                    ->boolean(),
                IconColumn::make('email_verified_at')
                    ->label(__('Verified'))
                    ->state(fn (User $record): bool => $record->hasVerifiedEmail())
                    ->boolean(),
                TextColumn::make('teams_count')
                    ->label(__('Teams'))
                    ->counts('teams')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Created at'))
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_admin')
                    ->label(__('Administrator')),
                TernaryFilter::make('email_verified_at')
                    ->label(__('Verified'))
                    ->nullable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->is(Auth::user()) || ! $record->canBeDeleted())
                    ->successNotification(fn (User $record): Notification => UserResource::getDeletedNotification($record)),
            ]);
    }
}
