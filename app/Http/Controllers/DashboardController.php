<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected ReferralService $referralService
    ) {}

    public function __invoke(Request $request): Response
    {
        $email = strtolower($request->user()->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $user = $request->user();
        $guestData = null;

        if ($user && $user->is_guest) {
            $expiresAt = $user->guest_expires_at;
            $hoursLeft = $expiresAt ? max(0, (int) now()->diffInHours($expiresAt, false)) : 0;
            $guestData = [
                'is_guest' => true,
                'expires_at' => $expiresAt?->toIso8601String(),
                'expires_in_hours' => $hoursLeft,
            ];
        }

        $referralStats = $user ? $this->referralService->getReferralStats($user) : null;

        return Inertia::render('Dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'guest' => $guestData,
            'referral' => $referralStats,
        ]);
    }
}
