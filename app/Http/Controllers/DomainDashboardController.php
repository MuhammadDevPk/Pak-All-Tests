<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\SduiPayloadTransformer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DomainDashboardController extends Controller
{
    public function __construct(
        protected SduiPayloadTransformer $transformer
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $domainSlug): Response
    {
        $user = $request->user();

        $domain = Domain::query()
            ->where('slug', $domainSlug)
            ->where('is_active', true)
            ->with([
                'flow' => fn ($query) => $query->where('is_active', true),
            ])
            ->firstOrFail();

        $activeSubscription = $user->subscriptions()
            ->where('domain_id', $domain->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $activeSubscription) {
            $latestSubscription = $user->subscriptions()
                ->where('domain_id', $domain->id)
                ->latest('id')
                ->first();

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
                'subscriptionStatus' => $latestSubscription?->status ?? 'none',
            ]);
        }

        $sduiPayload = $this->transformer->transform($domain, $user);

        return Inertia::render('Domain/Dashboard', [
            'sdui' => $sduiPayload,
        ]);
    }
}
