<?php

namespace App\Models;

use Database\Factories\TeamInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SensitiveParameter;

/**
 * Only a SHA-256 hash of the token is stored: the plain token exists in the
 * invitation email alone, so a database leak does not expose usable links.
 */
#[Fillable(['email', 'token_hash', 'invited_by_id', 'expires_at'])]
class TeamInvitation extends Model
{
    /** @use HasFactory<TeamInvitationFactory> */
    use HasFactory;

    use Prunable;

    public const ValidForDays = 7;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<User, $this> */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    public static function hashToken(#[SensitiveParameter] string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByToken(#[SensitiveParameter] string $token): ?self
    {
        return static::query()
            ->where('token_hash', static::hashToken($token))
            ->first();
    }

    /** @param Builder<self> $query */
    public function scopePending(Builder $query): void
    {
        $query->where('expires_at', '>', now());
    }

    /** @return Builder<self> */
    public function prunable(): Builder
    {
        return static::query()->where('expires_at', '<=', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isFor(User $user): bool
    {
        return strcasecmp($this->email, $user->email) === 0;
    }

    public function accept(User $user): Team
    {
        $team = $this->team;

        $team->users()->syncWithoutDetaching([$user->getKey()]);

        $this->delete();

        return $team;
    }
}
