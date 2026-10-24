<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\User;
use App\Services\GuestAuthService;
use App\Services\ReferralService;
use App\Services\SubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ConvertGuestAccountController extends Controller
{
    public function __construct(
        protected GuestAuthService $guestAuthService,
        protected SubscriptionManager $subscriptionManager,
        protected ReferralService $referralService
    ) {}

    /**
     * Convert an active ephemeral guest account into a permanent student account.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user || ! $user->is_guest) {
            return back()->with('error', 'Only guest accounts can be converted.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $this->guestAuthService->convertGuestToPermanent($user, $validated);

        // Grant standard 7-day free trial on interactive domain flows
        $eligibleDomains = Domain::query()->eligibleForSubscription()->get();
        foreach ($eligibleDomains as $domain) {
            $this->subscriptionManager->grantFreeTrial($user, $domain, 7);
        }

        // Process referral link if guest arrived via referral
        $refCode = $request->cookie('referral_code') ?? session('referral_code');
        $refDomainSlug = $request->cookie('referral_domain') ?? session('referral_domain');

        if ($refCode) {
            $targetDomain = $refDomainSlug
                ? Domain::query()->has('flow')->where('slug', $refDomainSlug)->where('is_active', true)->first()
                : Domain::query()->has('flow')->where('is_active', true)->first();

            if ($targetDomain) {
                $this->subscriptionManager->grantFreeTrial($user, $targetDomain, 7);
                $result = $this->referralService->processReferral(
                    childUser: $user,
                    referralCode: (string) $refCode,
                    domain: $targetDomain,
                    ipAddress: $request->ip(),
                    deviceFingerprint: $request->header('User-Agent')
                );

                if ($result['status'] === 'abuse_blocked' && ! empty($result['message'])) {
                    session()->flash('warning', $result['message']);
                }
            }
        }

        return back()->with('status', 'Account created successfully! Your test history has been preserved and a 7-Day Free Trial is now active.');
    }
}
