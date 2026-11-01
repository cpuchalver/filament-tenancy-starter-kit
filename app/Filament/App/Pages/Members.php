<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Widgets\PendingInvitations;
use App\Models\Team;
use App\Models\User;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class Members extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        $team = Filament::getTenant();

        return $team instanceof Team && Gate::allows('manageMembers', $team);
    }

    public static function getNavigationLabel(): string
    {
        return __('Members');
    }

    public function getTitle(): string
    {
        return __('Members');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->relationship(fn () => $this->team()->users())
            ->inverseRelationship('teams')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->badge(fn (User $record): bool => $this->team()->isOwnedBy($record))
                    ->color(fn (User $record): ?string => $this->team()->isOwnedBy($record) ? 'success' : null),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable(),
                TextColumn::make('pivot.created_at')
                    ->label(__('Joined at'))
                    ->date(),
            ])
            ->headerActions([
                $this->getInviteAction(),
            ])
            ->recordActions([
                Action::make('remove')
                    ->label(__('Remove'))
                    ->icon(Heroicon::OutlinedUserMinus)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => __('Remove :name from the team?', ['name' => $record->name]))
                    ->hidden(fn (User $record): bool => $this->team()->isOwnedBy($record))
                    ->action(function (User $record): void {
                        Gate::authorize('manageMembers', $this->team());

                        $this->team()->removeMember($record);

                        Notification::make()
                            ->title(__(':name has been removed from the team.', ['name' => $record->name]))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function getInviteAction(): Action
    {
        return Action::make('invite')
            ->label(__('Invite member'))
            ->icon(Heroicon::OutlinedEnvelope)
            ->schema([
                TextInput::make('email')
                    ->label(__('Email'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        if ($this->team()->hasMemberWithEmail($value)) {
                            $fail(__('This person is already a member of the team.'));
                        }
                    }),
            ])
            ->modalSubmitActionLabel(__('Send invitation'))
            ->action(function (array $data): void {
                Gate::authorize('manageMembers', $this->team());

                $this->team()->invite($data['email'], Auth::user());

                $this->dispatch('invitations-updated');

                Notification::make()
                    ->title(__('Invitation sent to :email.', ['email' => $data['email']]))
                    ->success()
                    ->send();
            });
    }

    protected function getFooterWidgets(): array
    {
        return [
            PendingInvitations::class,
        ];
    }

    protected function team(): Team
    {
        /** @var Team */
        return Filament::getTenant();
    }
}
