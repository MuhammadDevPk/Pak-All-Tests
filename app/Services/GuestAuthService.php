<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Teams\CreateTeam;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GuestAuthService
{
    /**
     * Cookie name for persistent guest device identity token.
     */
    public const COOKIE_NAME = 'pak_guest_token';

    /**
     * Persistent cookie lifetime in minutes (30 days).
     */
    public const COOKIE_LIFETIME_MINUTES = 60 * 24 * 30;

    public function __construct(
        protected CreateTeam $createTeam
    ) {}

    /**
     * Resolve an existing guest user via persistent device cookie OR system IP, or provision a fresh guest.
     * Guarantees that opening multiple browsers (Chrome, Safari, Edge, Firefox) on the same machine
     * reuses the exact same guest account instead of duplicating guest sign-ins.
     */
    public function getOrCreateGuestUser(Request $request): User
    {
        // 1. Primary Anchor: Deterministic resolution via persistent device token cookie
        $cookie = $request->cookie(self::COOKIE_NAME);
        if (is_string($cookie) && $cookie !== '') {
            $token = $cookie;

            /** @var User|null $existingGuest */
            $existingGuest = User::query()
                ->where(function ($q) use ($token) {
                    $q->where('guest_token_hash', $token)
                        ->orWhere('guest_token_hash', hash('sha256', $token));
                })
                ->where('is_guest', true)
                ->first();

            if ($existingGuest) {
                $existingGuest->update([
                    'last_active_at' => now(),
                ]);

                Auth::guard('web')->login($existingGuest, remember: false);

                // Refresh cookie expiration window
                Cookie::queue(self::COOKIE_NAME, $token, self::COOKIE_LIFETIME_MINUTES);

                return $existingGuest;
            }
        }

        // 2. Secondary Anchor: System / Network IP Resolution
        // When opening the site in a different browser on the same system (no shared cookies),
        // resolve to the existing guest account associated with this IP.
        $ipAddress = $request->ip();

        if ($ipAddress) {
            /** @var User|null $ipGuest */
            $ipGuest = User::query()
                ->where('is_guest', true)
                ->where('registration_ip', $ipAddress)
                ->latest('id')
                ->first();

            if ($ipGuest) {
                $ipGuest->update([
                    'last_active_at' => now(),
                ]);

                Auth::guard('web')->login($ipGuest, remember: false);

                // Synchronize the existing device token cookie into this new browser
                if ($ipGuest->guest_token_hash) {
                    Cookie::queue(self::COOKIE_NAME, $ipGuest->guest_token_hash, self::COOKIE_LIFETIME_MINUTES);
                }

                return $ipGuest;
            }
        }

        // 3. Brand-new visitor on this IP & system: Provision fresh guest user with high-entropy token
        $token = bin2hex(random_bytes(32));

        $guest = $this->createGuestUser($ipAddress, $token);

        Cookie::queue(self::COOKIE_NAME, $token, self::COOKIE_LIFETIME_MINUTES);

        return $guest;
    }

    /**
     * Create an ephemeral guest user with 24 hours of platform access.
     */
    public function createGuestUser(?string $ipAddress = null, ?string $token = null): User
    {
        return DB::transaction(function () use ($ipAddress, $token) {
            $uniqueId = strtolower((string) Str::ulid());
            $guestNumber = mt_rand(1000, 9999);

            $guestDomain = (string) config('platform.trials.guest_email_domain', 'guest.pakalltests.local');
            $guestHours = (int) config('platform.trials.guest_pass_hours', 24);

            if ($token === null) {
                $token = bin2hex(random_bytes(32));
                Cookie::queue(self::COOKIE_NAME, $token, self::COOKIE_LIFETIME_MINUTES);
            }

            $user = User::create([
                'name' => "Guest Candidate #{$guestNumber}",
                'email' => "guest_{$uniqueId}@{$guestDomain}",
                'password' => Hash::make(Str::random(32)),
                'is_guest' => true,
                'guest_expires_at' => now()->addHours($guestHours),
                'guest_token_hash' => $token,
                'last_active_at' => now(),
                'registration_ip' => $ipAddress,
            ]);

            $this->createTeam->handle($user, $user->name."'s Workspace", isPersonal: true);

            Auth::guard('web')->login($user, remember: false);

            return $user;
        });
    }

    /**
     * Convert an ephemeral guest user into a permanent student account.
     *
     * @param  array{name: string, email: string, password: string}  $credentials
     */
    public function convertGuestToPermanent(User $guest, array $credentials): User
    {
        return DB::transaction(function () use ($guest, $credentials) {
            $guest->update([
                'name' => $credentials['name'],
                'email' => $credentials['email'],
                'password' => Hash::make($credentials['password']),
                'is_guest' => false,
                'guest_expires_at' => null,
                'guest_token_hash' => null,
            ]);

            // Update team name to match user's real name
            if ($guest->personalTeam()) {
                $guest->personalTeam()->update([
                    'name' => $credentials['name']."'s Workspace",
                ]);
            }

            // Revoke the persistent guest device cookie on permanent conversion
            Cookie::queue(Cookie::forget(self::COOKIE_NAME));

            return $guest;
        });
    }
}
