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
     * Issue an instant 1-day guest pass without requiring account registration.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $guest = $this->guestAuthService->createGuestUser($request->ip());

        $domainSlug = $request->input('domain_slug');

        if ($domainSlug) {
            $domain = Domain::query()->eligibleForSubscription()->where('slug', $domainSlug)->first();
            if ($domain) {
                // Grant initial domain access
                $this->subscriptionManager->grantFreeTrial($guest, $domain, 1);

                return redirect()->route('domain.dashboard', ['domainSlug' => $domain->slug])
                    ->with('status', 'Your 24-Hour Guest Pass is active! Enjoy full access to all practice questions and AI mentorship.');
            }
        }

        return redirect()->route('dashboard')
            ->with('status', 'Your 24-Hour Guest Pass is active!');
    }
}
