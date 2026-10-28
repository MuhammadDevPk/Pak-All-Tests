<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\GuestAuthService;
use App\Services\SubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GuestLoginController extends Controller
{
    public function __construct(
        protected GuestAuthService $guestAuthService,
        protected SubscriptionManager $subscriptionManager
    ) {}

    /**
     * Issue or resume an instant 1-day guest pass without requiring account registration.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $guest = $this->guestAuthService->getOrCreateGuestUser($request);

        $domainSlug = $request->input('domain_slug');

        if ($domainSlug) {
            $domain = Domain::query()->eligibleForSubscription()->where('slug', (string) $domainSlug)->first();
            if ($domain) {
                // Grant initial domain access only if the guest pass is still active and unprovisioned
                if ($guest->guest_expires_at?->isFuture() && ! $guest->subscriptions()->where('domain_id', $domain->id)->exists()) {
                    $this->subscriptionManager->grantFreeTrial($guest, $domain, 1);
                }

                if ($guest->guest_expires_at?->isPast()) {
                    return redirect()->route('domain.dashboard', ['domainSlug' => $domain->slug])
                        ->with('warning', 'Your 24-hour guest window has completed. Please register to keep access to your exam dashboard.');
                }

                return redirect()->route('domain.dashboard', ['domainSlug' => $domain->slug])
                    ->with('status', 'Your 24-Hour Guest Pass is active! Enjoy full access to all practice questions and AI mentorship.');
            }
        }

        if ($guest->guest_expires_at?->isPast()) {
            return redirect()->route('dashboard')
                ->with('warning', 'Your 24-hour guest window has completed. Please register to keep access to your exam dashboard.');
        }

        return redirect()->route('dashboard')
            ->with('status', 'Your 24-Hour Guest Pass is active!');
    }
}
