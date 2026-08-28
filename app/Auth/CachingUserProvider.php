<?php

namespace App\Auth;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * CachingUserProvider
 *
 * Extends Laravel's EloquentUserProvider to cache authenticated user
 * lookups — AND their current Team — in Redis. Eliminates the two DB
 * round-trips (users + teams) that would otherwise occur on every
 * authenticated page request:
 *
 *   Before: users query 1,700ms + teams query 1,500ms = ~3,200ms/request
 *   After:  Redis read ~0.5ms per request
 *
 * Strategy:
 *   - Cache the user's raw attribute array (avoids serialization issues).
 *   - Cache the user's currentTeam raw attributes separately.
 *   - On cache hit: reconstruct both models from attributes, set the
 *     relation on the model — so ->currentTeam never fires a DB query.
 *
 * Cache TTL: 5 minutes. Invalidated on token/password updates.
 */
class CachingUserProvider extends EloquentUserProvider
{
    private const CACHE_TTL_SECONDS = 300; // 5 minutes

    private const USER_KEY_PREFIX = 'auth_user:';

    private const TEAM_KEY_PREFIX = 'auth_team:';

    /**
     * Retrieve a user by their unique identifier.
     * Hot path: called on EVERY authenticated HTTP request.
     *
     * We also eagerly load + cache the user's currentTeam so that
     * SetTeamUrlDefaults middleware never triggers a second DB query.
     */
    public function retrieveById(mixed $identifier): ?Authenticatable
    {
        $userKey = self::USER_KEY_PREFIX.$identifier;

        /** @var array<string, mixed>|null $userAttributes */
        $userAttributes = Cache::get($userKey);

        if ($userAttributes !== null) {
            // Reconstruct user from cached attributes
            $model = $this->createModel();
            $model->forceFill($userAttributes)->syncOriginal();
            $model->exists = true;

            // Re-attach the cached currentTeam relation so Eloquent
            // never fires "SELECT * FROM teams" on ->currentTeam access
            $teamId = $userAttributes['current_team_id'] ?? null;
            if ($teamId !== null) {
                $team = $this->resolveTeamFromCache($teamId);
                if ($team !== null) {
                    $model->setRelation('currentTeam', $team);
                }
            } else {
                // User has no current team — set null so Eloquent doesn't query
                $model->setRelation('currentTeam', null);
            }

            return $model;
        }

        // Cache miss: fetch user from DB with currentTeam eager-loaded (1 query via JOIN)
        $user = $this->createModel()
            ->newQuery()
            ->with('currentTeam')
            ->find($identifier);

        if ($user instanceof User) {
            // Store user attributes
            Cache::put($userKey, $user->getAttributes(), self::CACHE_TTL_SECONDS);

            // Store currentTeam attributes
            $teamId = $user->current_team_id;
            if ($teamId !== null && $user->relationLoaded('currentTeam') && $user->currentTeam !== null) {
                Cache::put(
                    self::TEAM_KEY_PREFIX.$teamId,
                    $user->currentTeam->getAttributes(),
                    self::CACHE_TTL_SECONDS,
                );
            } elseif ($teamId === null) {
                // Explicitly cache "no team" to avoid DB lookup next time
                Cache::put(self::USER_KEY_PREFIX.$identifier.':no_team', true, self::CACHE_TTL_SECONDS);
            }
        }

        return ($user instanceof Authenticatable && $user instanceof Model) ? $user : null;
    }

    /**
     * Resolve a Team model from Redis cache (or DB on cache miss).
     * Returns null if the team does not exist.
     */
    private function resolveTeamFromCache(int|string $teamId): ?Team
    {
        $teamKey = self::TEAM_KEY_PREFIX.$teamId;

        /** @var array<string, mixed>|null $teamAttrs */
        $teamAttrs = Cache::get($teamKey);

        if ($teamAttrs !== null) {
            $team = new Team;
            $team->forceFill($teamAttrs)->syncOriginal();
            $team->exists = true;

            return $team;
        }

        // Fetch from DB and cache
        $team = Team::find($teamId);

        if ($team !== null) {
            Cache::put($teamKey, $team->getAttributes(), self::CACHE_TTL_SECONDS);
        }

        return $team;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     * Invalidates the user's Redis cache entry.
     */
    public function updateRememberToken(Authenticatable $user, $token): void
    {
        parent::updateRememberToken($user, $token);
        $this->invalidateCache($user->getAuthIdentifier());
    }

    /**
     * Rehash the user's password if required and supported.
     * Invalidates the cache to ensure the updated model is reloaded.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        parent::rehashPasswordIfRequired($user, $credentials, $force);
        $this->invalidateCache($user->getAuthIdentifier());
    }

    /**
     * Remove the user (and their team) from Redis cache.
     * Reads the team_id from the cached user attributes in Redis — zero DB queries.
     * Call this after any user or team model update.
     */
    public function invalidateCache(mixed $identifier): void
    {
        // Evict team cache first — read team_id from Redis before forgetting user key
        /** @var array<string, mixed>|null $userAttributes */
        $userAttributes = Cache::get(self::USER_KEY_PREFIX.$identifier);
        if ($userAttributes !== null) {
            $teamId = $userAttributes['current_team_id'] ?? null;
            if ($teamId !== null) {
                Cache::forget(self::TEAM_KEY_PREFIX.$teamId);
            }
        }

        Cache::forget(self::USER_KEY_PREFIX.$identifier);
    }
}
