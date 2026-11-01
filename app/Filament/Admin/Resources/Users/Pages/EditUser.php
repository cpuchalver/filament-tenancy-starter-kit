<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
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
                ->hidden(fn (User $record): bool => $record->is(Auth::user()) || ! $record->canBeDeleted()),
        ];
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
