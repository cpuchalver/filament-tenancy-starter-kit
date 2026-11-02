<?php

namespace App\Models;

use App\Notifications\TeamInvitationNotification;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use LogicException;

#[Fillable(['name', 'slug'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    /**
     * Panel URL segments that a team slug would shadow, since tenant routes have no prefix.
     *
     * @var array<int, string>
     */
    public const ReservedSlugs = [
        'new',
        'login',
        'logout',
        'password-reset',
        'email-verification',
        'email-change-verification',
        'invitations',
    ];

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /** @return HasMany<TeamInvitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner()->is($user);
    }

    public function hasMemberWithEmail(string $email): bool
    {
        return $this->users()->whereRaw('lower(email) = ?', [Str::lower($email)])->exists();
    }

    /**
     * Inviting an address that already has a pending invitation renews it,
     * which invalidates the link sent previously.
     */
    public function invite(string $email, User $inviter): TeamInvitation
    {
        $token = Str::random(64);

        $invitation = $this->invitations()->updateOrCreate(
            ['email' => Str::lower($email)],
            [
                'token_hash' => TeamInvitation::hashToken($token),
                'invited_by_id' => $inviter->getKey(),
                'expires_at' => now()->addDays(TeamInvitation::ValidForDays),
            ],
        );

        Notification::route('mail', $invitation->email)
            ->notify(new TeamInvitationNotification($invitation, $token));

        return $invitation;
    }

    public function removeMember(User $member): void
    {
        if ($this->isOwnedBy($member)) {
            throw new LogicException('The owner cannot be removed from their team.');
        }

        $this->users()->detach($member);
    }

    public function assignOwner(User $owner): void
    {
        $this->owner()->associate($owner);
        $this->save();

        $this->users()->syncWithoutDetaching([$owner->getKey()]);
    }
}
