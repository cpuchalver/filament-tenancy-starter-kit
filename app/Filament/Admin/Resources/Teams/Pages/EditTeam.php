<?php

namespace App\Filament\Admin\Resources\Teams\Pages;

use App\Filament\Admin\Resources\Teams\TeamResource;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(fn (DeleteAction $action, Team $record): DeleteAction => $action->successNotification(
                    Notification::make()
                        ->title(__('Team deleted'))
                        ->body(__(':team and its memberships were deleted.', ['team' => $record->name]))
                        ->success(),
                )),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title(__('Team saved'))
            ->success();
    }

    /** @param Team $record */
    protected function handleRecordUpdate(Model $record, array $data): Team
    {
        $record->fill($data);
        $record->assignOwner(User::findOrFail($data['owner_id']));

        return $record;
    }
}
