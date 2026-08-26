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

        $subscription = $user->subscriptions()
            ->where('domain_id', $domain->id)
            ->where('status', 'active')
            ->first();

        if (! $subscription) {
            return Inertia::render('Subscription/PendingOrInactive', [
                'domain' => [
                    'id' => $domain->id,
                    'name' => $domain->name,
                    'slug' => $domain->slug,
                ],
                'subscriptionStatus' => 'none',
            ]);
        }

        $sduiPayload = $this->transformer->transform($domain, $user);

        return Inertia::render('Domain/Dashboard', [
            'sdui' => $sduiPayload,
        ]);
    }
}
