<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): User
    {
        $user = new User($data);
        $user->is_admin = $data['is_admin'] ?? false;
        $user->save();

        return $user;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title(__('User created'))
            ->body(__(':email will be asked to verify their address at first sign-in.', ['email' => $this->record->email]))
            ->success();
    }
}
