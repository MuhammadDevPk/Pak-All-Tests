<?php

use App\Models\Domain;
use App\Models\DomainFlow;
use App\Models\User;
use App\Models\UserSubscription;
use Inertia\Testing\AssertableInertia as Assert;

test('subscribers can access their domain dashboard', function () {
    $user = User::factory()->create();

    $domain = Domain::create([
        'name' => 'CSS Exam Prep',
        'slug' => 'css-exam',
        'code' => 'CSS',
        'is_active' => true,
    ]);

    DomainFlow::create([
        'domain_id' => $domain->id,
        'ui_schema' => [
            'type' => 'EmptyStateLayout',
            'props' => ['title' => 'Under Construction'],
        ],
    ]);

    UserSubscription::create([
        'user_id' => $user->id,
        'domain_id' => $domain->id,
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addYear(),
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('domain.dashboard', ['domainSlug' => $domain->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Domain/Dashboard')
        ->has('sdui.layout')
        ->where('sdui.meta.domain.name', 'CSS Exam Prep')
    );
});

test('unsubscribed users are redirected to the inactive page', function () {
    $user = User::factory()->create();

    $domain = Domain::create([
        'name' => 'CSS Exam Prep',
        'slug' => 'css-exam',
        'code' => 'CSS',
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('domain.dashboard', ['domainSlug' => $domain->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Subscription/PendingOrInactive')
        ->where('subscriptionStatus', 'none')
    );
});
