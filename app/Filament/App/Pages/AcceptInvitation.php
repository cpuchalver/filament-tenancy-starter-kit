<?php

namespace App\Filament\App\Pages;

use App\Models\TeamInvitation;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Dashboard;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * Reachable without signing in: the token in the URL is the proof that the
 * visitor received the invitation email.
 */
class AcceptInvitation extends SimplePage
{
    #[Locked]
    public string $token;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getUrlForToken(string $token): string
    {
        return route('filament.app.invitations.accept', ['token' => $token]);
    }

    public function mount(string $token): void
    {
        $this->token = $token;

        if ($this->isUsable() && $this->invitedUserExists() && Filament::auth()->guest()) {
            redirect()->setIntendedUrl(request()->fullUrl());
        }

        $this->form->fill();
    }

    #[Computed]
    public function invitation(): ?TeamInvitation
    {
        return TeamInvitation::findByToken($this->token);
    }

    protected function isUsable(): bool
    {
        return $this->invitation !== null && ! $this->invitation->isExpired();
    }

    public function getTitle(): string|Htmlable
    {
        return __('Team invitation');
    }

    public function getHeading(): string|Htmlable|null
    {
        if (! $this->invitation) {
            return __('Invitation unavailable');
        }

        if ($this->invitation->isExpired()) {
            return __('Invitation expired');
        }

        return __('Join :team', ['team' => $this->invitation->team->name]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),
                TextInput::make('password')
                    ->label(__('Password'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(Password::default())
                    ->same('passwordConfirmation'),
                TextInput::make('passwordConfirmation')
                    ->label(__('Confirm password'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->dehydrated(false),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        $invitation = $this->invitation;

        if (! $invitation) {
            return $schema->components([
                Callout::make(__('This invitation link is invalid.'))
                    ->description(__('It may have been revoked or replaced by a newer invitation. Ask the team owner to send you a new one.'))
                    ->danger(),
            ]);
        }

        if ($invitation->isExpired()) {
            return $schema->components([
                Callout::make(__('This invitation to join :team expired on :date.', [
                    'team' => $invitation->team->name,
                    'date' => $invitation->expires_at->toFormattedDayDateString(),
                ]))
                    ->description(__('Ask the team owner to send you a new invitation.'))
                    ->warning(),
            ]);
        }

        $user = Filament::auth()->user();

        if ($user instanceof User && ! $invitation->isFor($user)) {
            return $schema->components([
                Callout::make(__('This invitation was sent to :email.', ['email' => $invitation->email]))
                    ->description(__('You are signed in as :email. Sign out, then open the invitation link again.', ['email' => $user->email]))
                    ->warning(),
                Actions::make([
                    Action::make('logout')
                        ->label(__('Sign out'))
                        ->color('gray')
                        ->url(Filament::getLogoutUrl())
                        ->postToUrl(),
                ])->fullWidth(),
            ]);
        }

        if ($user instanceof User) {
            return $schema->components([
                Text::make(__('You have been invited to join :team.', ['team' => $invitation->team->name])),
                Actions::make([
                    Action::make('join')
                        ->label(__('Join :team', ['team' => $invitation->team->name]))
                        ->action('join'),
                ])->fullWidth(),
            ]);
        }

        if ($this->invitedUserExists()) {
            return $schema->components([
                Text::make(__('An account already exists for :email. Sign in to join :team.', [
                    'email' => $invitation->email,
                    'team' => $invitation->team->name,
                ])),
                Actions::make([
                    Action::make('login')
                        ->label(__('Sign in'))
                        ->url(Filament::getLoginUrl()),
                ])->fullWidth(),
            ]);
        }

        return $schema->components([
            Text::make(__('Create your account for :email to join :team.', [
                'email' => $invitation->email,
                'team' => $invitation->team->name,
            ])),
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('register')
                ->footer([
                    Actions::make([
                        Action::make('register')
                            ->label(__('Create account and join'))
                            ->submit('register'),
                    ])->fullWidth(),
                ]),
        ]);
    }

    public function join(): void
    {
        $invitation = $this->invitation;
        $user = Filament::auth()->user();

        if (! $this->isUsable() || ! $user instanceof User || ! $invitation->isFor($user)) {
            return;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $this->redirect(Dashboard::getUrl(panel: 'app', tenant: $invitation->accept($user)));
    }

    public function register(): void
    {
        $invitation = $this->invitation;

        if (! $this->isUsable() || Filament::auth()->check() || $this->invitedUserExists()) {
            return;
        }

        $data = $this->form->getState();

        $user = new User([
            'name' => $data['name'],
            'email' => $invitation->email,
            'password' => $data['password'],
        ]);
        $user->markEmailAsVerified();

        Filament::auth()->login($user);
        session()->regenerate();

        $this->redirect(Dashboard::getUrl(panel: 'app', tenant: $invitation->accept($user)));
    }

    protected function invitedUserExists(): bool
    {
        return User::whereRaw('lower(email) = ?', [$this->invitation?->email])->exists();
    }
}
