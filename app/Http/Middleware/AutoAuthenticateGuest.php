<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Domain;
use App\Models\PlatformSetting;
use App\Services\GuestAuthService;
use App\Services\SubscriptionManager;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AutoAuthenticateGuest
{
    public function __construct(
        protected GuestAuthService $guestAuthService,
        protected SubscriptionManager $subscriptionManager
    ) {}

    /**
     * Handle an incoming request.
     *
     * Seamlessly provisions or re-authenticates an ephemeral guest user on direct URL visits,
     * maintaining 24-hour domain access across browser restarts without duplicating guest accounts.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Defensive guard: Admin panel routes must never auto-authenticate as guests
        if ($request->is('admin*')) {
            return $next($request);
        }

        if (Auth::guard('web')->check()) {
            return $next($request);
        }

        // If visitor arrived via an explicit referral link (?ref=...), or auto-guest is disabled,
        // trigger standard authentication exception (handles expectsJson 401 or redirectGuestsTo).
        if ($request->filled('ref') || ! PlatformSetting::isAutoGuestLoginEnabled()) {
            throw new AuthenticationException('Unauthenticated.');
        }

        // Resolve existing guest via persistent device token or provision a fresh guest
        $guest = $this->guestAuthService->getOrCreateGuestUser($request);
        $request->setUserResolver(fn () => $guest);

        // Resolve requested domain if accessing a domain-scoped route
        $domainSlug = $request->route('domainSlug')
            ?? $request->query('domain')
            ?? $request->query('domain_slug');

        if ($domainSlug) {
            $domain = Domain::query()->eligibleForSubscription()->where('slug', (string) $domainSlug)->first();
            // Only provision initial trial if guest pass is active AND subscription does not yet exist
            if ($domain && $guest->guest_expires_at?->isFuture() && ! $guest->subscriptions()->where('domain_id', $domain->id)->exists()) {
                $this->subscriptionManager->grantFreeTrial($guest, $domain, 1);
            }
        }

        if ($guest->guest_expires_at?->isPast()) {
            session()->flash('warning', 'Your 24-hour guest window has completed. Please register to keep access to your exam dashboard.');
        } else {
            session()->flash('status', 'Your 24-Hour Guest Pass is active! Enjoy full access to all practice questions and AI mentorship.');
        }

        Log::info('guest.auto_authenticated', [
            'user_id' => $guest->id,
            'is_expired' => $guest->guest_expires_at?->isPast(),
            'domain_slug' => $domainSlug,
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}
