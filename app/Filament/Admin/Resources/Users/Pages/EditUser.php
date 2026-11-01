<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (User $record): bool => $record->is(Auth::user()) || ! $record->canBeDeleted())
                ->before(fn (DeleteAction $action, User $record): DeleteAction => $action->successNotification(UserResource::getDeletedNotification($record))),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title(__('User saved'))
            ->success();
    }

    /** @param User $record */
    protected function handleRecordUpdate(Model $record, array $data): User
    {
        $record->fill($data);

        if (! $record->is(Auth::user())) {
            $record->is_admin = $data['is_admin'] ?? $record->is_admin;
        }

        $record->save();

        return $record;
    }
}
