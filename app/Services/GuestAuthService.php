<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Teams\CreateTeam;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GuestAuthService
{
    public function __construct(
        protected CreateTeam $createTeam
    ) {}

    /**
     * Create an ephemeral guest user with 24 hours of platform access.
     */
    public function createGuestUser(?string $ipAddress = null): User
    {
        return DB::transaction(function () use ($ipAddress) {
            $uniqueId = strtolower((string) Str::ulid());
            $guestNumber = mt_rand(1000, 9999);

            $user = User::create([
                'name' => "Guest Candidate #{$guestNumber}",
                'email' => "guest_{$uniqueId}@guest.pakalltests.local",
                'password' => Hash::make(Str::random(32)),
                'is_guest' => true,
                'guest_expires_at' => now()->addHours(24),
                'registration_ip' => $ipAddress,
            ]);

            $this->createTeam->handle($user, $user->name."'s Workspace", isPersonal: true);

            Auth::login($user, remember: true);

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
            ]);

            // Update team name to match user's real name
            if ($guest->personalTeam()) {
                $guest->personalTeam()->update([
                    'name' => $credentials['name']."'s Workspace",
                ]);
            }

            return $guest;
        });
    }
}
