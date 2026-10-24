<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Models\Domain;
use App\Models\DomainFlow;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class OnDemandSubscriptionProvisioningTest extends TestCase
{
    use DatabaseTransactions;

    protected function createDomainWithFlow(string $name = 'Test Course Flow'): Domain
    {
        $slug = Str::slug($name).'-'.uniqid();
        $domain = Domain::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'requires_subscription' => true,
        ]);

        DomainFlow::create([
            'domain_id' => $domain->id,
            'ui_schema' => ['type' => 'VerticalStack', 'props' => []],
            'version' => 1,
            'is_active' => true,
        ]);

        return $domain;
    }

    public function test_user_registration_provisions_flow_domains_and_never_parent_categories(): void
    {
        $parentDomain = Domain::create([
            'name' => 'Parent Military Force '.uniqid(),
            'slug' => 'parent-force-'.uniqid(),
            'is_active' => true,
            'requires_subscription' => true,
        ]);

        $childFlowDomain = Domain::create([
            'parent_id' => $parentDomain->id,
            'name' => 'PMA Initial '.uniqid(),
            'slug' => 'pma-initial-'.uniqid(),
            'is_active' => true,
            'requires_subscription' => true,
        ]);

        DomainFlow::create([
            'domain_id' => $childFlowDomain->id,
            'ui_schema' => ['type' => 'VerticalStack', 'props' => []],
            'version' => 1,
            'is_active' => true,
        ]);

        $action = app(CreateNewUser::class);
        $user = $action->create([
            'name' => 'Candidate Test User',
            'email' => 'candidate_'.uniqid().'@pakalltests.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // Child domain flow must have a subscription
        $this->assertTrue(
            $user->subscriptions()->where('domain_id', $childFlowDomain->id)->exists(),
            'Registration must provision trial for interactive domain flow.'
        );

        // Parent category domain must NEVER have a subscription
        $this->assertFalse(
            $user->subscriptions()->where('domain_id', $parentDomain->id)->exists(),
            'Registration must NEVER provision subscriptions for parent category folders.'
        );
    }

    public function test_guest_visiting_domain_flow_provisions_one_day_trial_on_demand(): void
    {
        PlatformSetting::set('all_domains_free', false);

        $flowDomain = $this->createDomainWithFlow('Guest Target Flow');

        $guest = User::create([
            'name' => 'Guest Candidate #1234',
            'email' => 'guest_'.uniqid().'@guest.pakalltests.local',
            'password' => Hash::make(Str::random(32)),
            'is_guest' => true,
            'guest_expires_at' => now()->addHours(24),
        ]);

        $this->assertSame(0, $guest->subscriptions()->count());

        $response = $this->actingAs($guest)->get(route('domain.dashboard', ['domainSlug' => $flowDomain->slug]));

        $response->assertOk();

        $sub = $guest->subscriptions()->where('domain_id', $flowDomain->id)->first();
        $this->assertNotNull($sub);
        $this->assertSame('active', $sub->status);
        $this->assertSame('free_trial', $sub->payment_method);
    }

    public function test_subsequent_visits_do_not_duplicate_subscriptions(): void
    {
        $flowDomain = $this->createDomainWithFlow('Single Subscription Test');

        $user = User::create([
            'name' => 'Candidate Multi Visit',
            'email' => 'multi_'.uniqid().'@test.com',
            'password' => Hash::make('Secret123!'),
            'is_guest' => false,
        ]);

        UserSubscription::create([
            'user_id' => $user->id,
            'domain_id' => $flowDomain->id,
            'status' => 'active',
            'payment_method' => 'free_trial',
            'starts_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($user)->get(route('domain.dashboard', ['domainSlug' => $flowDomain->slug]));
        $this->actingAs($user)->get(route('domain.dashboard', ['domainSlug' => $flowDomain->slug]));

        $this->assertSame(1, $user->subscriptions()->where('domain_id', $flowDomain->id)->count());
    }

    public function test_user_subscriptions_tabs_filter_registered_vs_guest_candidates(): void
    {
        $domain = $this->createDomainWithFlow('Tab Filter Test');

        $registeredUser = User::create([
            'name' => 'Permanent Student',
            'email' => 'student_'.uniqid().'@test.com',
            'password' => Hash::make('Secret123!'),
            'is_guest' => false,
        ]);

        $guestUser = User::create([
            'name' => 'Guest Visitor',
            'email' => 'guest_'.uniqid().'@guest.pakalltests.local',
            'password' => Hash::make(Str::random(32)),
            'is_guest' => true,
            'guest_expires_at' => now()->addHours(24),
        ]);

        UserSubscription::create([
            'user_id' => $registeredUser->id,
            'domain_id' => $domain->id,
            'status' => 'active',
            'payment_method' => 'free_trial',
            'starts_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        UserSubscription::create([
            'user_id' => $guestUser->id,
            'domain_id' => $domain->id,
            'status' => 'active',
            'payment_method' => 'free_trial',
            'starts_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $registeredQuery = UserSubscription::whereHas('user', fn ($q) => $q->where('is_guest', false));
        $guestQuery = UserSubscription::whereHas('user', fn ($q) => $q->where('is_guest', true));

        $this->assertTrue($registeredQuery->where('user_id', $registeredUser->id)->exists());
        $this->assertFalse($registeredQuery->where('user_id', $guestUser->id)->exists());

        $this->assertTrue($guestQuery->where('user_id', $guestUser->id)->exists());
        $this->assertFalse($guestQuery->where('user_id', $registeredUser->id)->exists());
    }
}
