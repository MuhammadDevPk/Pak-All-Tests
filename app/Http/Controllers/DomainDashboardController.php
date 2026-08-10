<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\Request;
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
            ->with(['flow', 'contentModules'])
            ->firstOrFail();

        $subscription = $user->subscriptions()
            ->where('doman_id', $domain->id)
            ->where('status', 'active')
            ->first();

        if (! $subscription) {
            return Inertia::render('Subscription/PendingOrInactive',
                [
                    'domain' => $domain->id,
                    'name' => $domain->name,
                    'slug' => $domain->slug,
                ],
                [
                    'subscriptionStatus' => $subscription?->status ?? 'none',
                ]);
        }

        // $sduiPayload = $this->transformer->transform($domain, $user);

    }
}
