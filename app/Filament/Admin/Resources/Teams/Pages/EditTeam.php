<?php

namespace App\Filament\Admin\Resources\Teams\Pages;

use App\Filament\Admin\Resources\Teams\TeamResource;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /** @param Team $record */
    protected function handleRecordUpdate(Model $record, array $data): Team
    {
        $record->fill($data);
        $record->assignOwner(User::findOrFail($data['owner_id']));

        return $record;
    }
}
