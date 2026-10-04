<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasTeams;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property bool $is_guest
 * @property Carbon|null $guest_expires_at
 * @property bool $is_suspended
 * @property string|null $suspension_reason
 * @property string|null $referral_code
 * @property int|null $referred_by_id
 * @property string|null $registration_ip
 * @property-read Team|null $currentTeam
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read Collection<int, Membership> $teamMemberships
 * @property-read Collection<int, Team> $teams
 */
#[Fillable(['name', 'email', 'password', 'current_team_id', 'is_guest', 'guest_expires_at', 'is_suspended', 'suspension_reason', 'referral_code', 'referred_by_id', 'registration_ip'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTeams, Notifiable, PasskeyAuthenticatable;

    public function canAccessPanel(Panel $panel): bool
    {
        // Suspended users cannot access the panel
        if ($this->is_suspended) {
            return false;
        }

        // For Development allow authenticated users
        return true;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_guest' => 'boolean',
            'guest_expires_at' => 'datetime',
            'is_suspended' => 'boolean',
        ];
    }

    /**
     * @return HasMany<UserSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Determine if the user holds an active, non-expired subscription for a domain.
     */
    public function hasActiveSubscriptionFor(Domain $domain): bool
    {
        return $this->activeSubscriptionFor($domain) !== null;
    }

    /**
     * Retrieve the user's active, non-expired subscription for a domain.
     */
    public function activeSubscriptionFor(Domain $domain): ?UserSubscription
    {
        return $this->subscriptions()
            ->where('domain_id', $domain->id)
            ->active()
            ->first();
    }

    /**
     * @return BelongsToMany<Domain, $this>
     */
    public function subscribedDomains(): BelongsToMany
    {
        return $this->belongsToMany(Domain::class, 'user_subscriptions', 'user_id', 'domain_id')
            ->withPivot([
                'status',
                'receipt_url',
                'payment_method',
                'transaction_id',
                'rejection_reason',
                'starts_at',
                'expires_at',
            ])
            ->withTimestamps();
    }

    /**
     * @return HasMany<DomainChatMessage, $this>
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(DomainChatMessage::class);
    }

    /**
     * @return HasMany<UserWeakQuestion, $this>
     */
    public function weakQuestions(): HasMany
    {
        return $this->hasMany(UserWeakQuestion::class);
    }

    /**
     * @return HasMany<Referral, $this>
     */
    public function referralsGiven(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * @return HasMany<Referral, $this>
     */
    public function referralsReceived(): HasMany
    {
        return $this->hasMany(Referral::class, 'referred_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_id');
    }
}
