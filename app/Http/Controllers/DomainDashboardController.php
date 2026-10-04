<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\ReferralService;
use App\Services\SduiPayloadTransformer;
use App\Services\SubscriptionManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DomainDashboardController extends Controller
{
    public function __construct(
        protected SduiPayloadTransformer $transformer,
        protected SubscriptionManager $subscriptionManager,
        protected ReferralService $referralService
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $domainSlug): Response
    {
        $user = $request->user();

        // Capture referral code if candidate arrived via share link
        if ($request->filled('ref')) {
            $refCode = (string) $request->query('ref');

            // Detect and warn against self-referral attempts
            if ($user && $user->referral_code && $user->referral_code === $refCode) {
                session()->flash('warning', 'Notice: You cannot refer yourself using your own link. Share your referral link with classmates and friends to earn free access!');
            } else {
                cookie()->queue('referral_code', $refCode, 60 * 24 * 7);
                cookie()->queue('referral_domain', $domainSlug, 60 * 24 * 7);
            }
        }

        $domain = Domain::query()
            ->where('slug', $domainSlug)
            ->where('is_active', true)
            ->with([
                'flow' => fn ($query) => $query->where('is_active', true),
            ])
            ->firstOrFail();

        $hasAccess = $this->subscriptionManager->hasActiveAccess($user, $domain);

        if (! $hasAccess) {
            $latestSubscription = $this->subscriptionManager->getLatestSubscription($user, $domain);

            $referralStats = $user ? $this->referralService->getReferralStats($user, $domain) : null;

            return Inertia::render('Subscription/PendingOrInactive', [
                'domain' => [
                    'id' => $domain->id,
                    'name' => $domain->name,
                    'slug' => $domain->slug,
                ],
                'subscription' => $latestSubscription ? [
                    'id' => $latestSubscription->id,
                    'status' => $latestSubscription->status,
                    'payment_method' => $latestSubscription->payment_method,
                    'transaction_id' => $latestSubscription->transaction_id,
                    'receipt_url' => $latestSubscription->receipt_url ? asset('storage/'.$latestSubscription->receipt_url) : null,
                    'rejection_reason' => $latestSubscription->rejection_reason,
                    'created_at' => $latestSubscription->created_at?->diffForHumans(),
                ] : null,
                'subscriptionStatus' => $latestSubscription ? $latestSubscription->status : 'none',
                'isSuspended' => (bool) $user?->is_suspended,
                'suspensionReason' => $user?->suspension_reason,
                'referral' => $referralStats,
            ]);
        }

        $sduiPayload = $this->transformer->transform($domain, $user);
        $referralStats = $this->referralService->getReferralStats($user, $domain);

        return Inertia::render('Domain/Dashboard', [
            'sdui' => $sduiPayload,
            'referral' => $referralStats,
            'guest' => [
                'is_guest' => (bool) $user->is_guest,
                'expires_at' => $user->guest_expires_at?->toIso8601String(),
                'expires_in_hours' => $user->guest_expires_at ? max(0, now()->diffInHours($user->guest_expires_at, false)) : null,
            ],
        ]);
    }
}
