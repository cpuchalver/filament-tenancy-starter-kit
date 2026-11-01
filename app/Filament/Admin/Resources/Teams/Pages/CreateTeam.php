<?php

namespace App\Filament\Admin\Resources\Teams\Pages;

use App\Filament\Admin\Resources\Teams\TeamResource;
use App\Models\Team;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    protected function handleRecordCreation(array $data): Team
    {
        $team = new Team($data);
        $team->assignOwner(User::findOrFail($data['owner_id']));

        return $team;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title(__('Team created'))
            ->body(__(':team is owned by :owner.', ['team' => $this->record->name, 'owner' => $this->record->owner->name]))
            ->success();
    }
}
