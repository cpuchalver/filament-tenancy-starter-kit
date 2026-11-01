<?php

namespace App\Filament\App\Widgets;

use App\Models\Team;
use App\Models\TeamInvitation;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;

class PendingInvitations extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $team = Filament::getTenant();

        return $team instanceof Team && Gate::allows('manageMembers', $team);
    }

    #[On('invitations-updated')]
    public function refreshInvitations(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Pending invitations'))
            ->query(fn (): Builder => TeamInvitation::query()
                ->whereBelongsTo($this->team())
                ->pending()
                ->with('invitedBy'))
            ->emptyStateHeading(__('No pending invitations'))
            ->columns([
                TextColumn::make('email')
                    ->label(__('Email')),
                TextColumn::make('invitedBy.name')
                    ->label(__('Invited by')),
                TextColumn::make('expires_at')
                    ->label(__('Expires at'))
                    ->date(),
            ])
            ->recordActions([
                Action::make('resend')
                    ->label(__('Resend'))
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('gray')
                    ->action(function (TeamInvitation $record): void {
                        Gate::authorize('manageMembers', $this->team());

                        $this->team()->invite($record->email, Auth::user());

                        Notification::make()
                            ->title(__('Invitation sent again to :email.', ['email' => $record->email]))
                            ->success()
                            ->send();
                    }),
                Action::make('revoke')
                    ->label(__('Revoke'))
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (TeamInvitation $record): void {
                        Gate::authorize('manageMembers', $this->team());

                        $record->delete();

                        Notification::make()
                            ->title(__('Invitation revoked.'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function team(): Team
    {
        /** @var Team */
        return Filament::getTenant();
    }
}
