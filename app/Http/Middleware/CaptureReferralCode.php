<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureReferralCode
{
    /**
     * Handle an incoming request.
     * Captures ?ref=... and optional ?domain=... or ?domainSlug=... across all web routes
     * and persists them in high-priority HTTP cookies and session.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $refCode = $request->filled('ref') ? trim((string) $request->query('ref')) : null;
        $domainSlug = $request->route('domainSlug')
            ?? $request->query('domain')
            ?? $request->query('domain_slug');

        if ($refCode) {
            $user = $request->user();

            // Self-referral detection for already authenticated user
            if ($user && $user->referral_code && $user->referral_code === $refCode) {
                session()->flash('warning', 'Notice: You cannot refer yourself using your own link. Share your referral link with classmates and friends to earn free access!');
            } else {
                cookie()->queue('referral_code', $refCode, 60 * 24 * 7);
                session()->put('referral_code', $refCode);

                if ($domainSlug) {
                    cookie()->queue('referral_domain', (string) $domainSlug, 60 * 24 * 7);
                    session()->put('referral_domain', (string) $domainSlug);
                }
            }
        }

        $response = $next($request);

        if ($refCode) {
            $response->headers->setCookie(cookie('referral_code', $refCode, 60 * 24 * 7));
            if ($domainSlug) {
                $response->headers->setCookie(cookie('referral_domain', (string) $domainSlug, 60 * 24 * 7));
            }
        }

        return $response;
    }
}
