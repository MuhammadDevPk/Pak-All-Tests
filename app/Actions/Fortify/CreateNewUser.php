<?php

namespace App\Actions\Fortify;

use App\Actions\Teams\CreateTeam;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Domain;
use App\Models\User;
use App\Services\ReferralService;
use App\Services\SubscriptionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(
        private CreateTeam $createTeam,
        private ReferralService $referralService,
        private SubscriptionManager $subscriptionManager
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'ref' => ['nullable', 'string', 'max:32', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! empty($value)) {
                    $normalized = strtoupper(trim((string) $value));
                    $exists = User::query()->where('referral_code', $normalized)->exists();
                    if (! $exists) {
                        $fail('The provided referral code is invalid or does not exist.');
                    }
                }
            }],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'registration_ip' => request()->ip(),
            ]);

            $this->createTeam->handle($user, $user->name."'s Workspace", isPersonal: true);

            // Provision Free Trial for active domain flows only (not parent category folders)
            $trialDays = (int) config('platform.trials.free_trial_days', 7);
            $flowDomains = Domain::query()->eligibleForSubscription()->get();
            foreach ($flowDomains as $domain) {
                $this->subscriptionManager->grantFreeTrial($user, $domain, $trialDays);
            }

            // Process referral link if present (normalized uppercase)
            $rawRefCode = ! empty($input['ref'])
                ? $input['ref']
                : (request()->query('ref') ?? request()->cookie('referral_code') ?? session('referral_code'));
            $refCode = ! empty($rawRefCode) ? strtoupper(trim((string) $rawRefCode)) : null;

            $refDomainSlug = $input['domain_slug'] ?? request()->query('domain_slug') ?? request()->query('domain') ?? request()->cookie('referral_domain') ?? session('referral_domain');

            if ($refCode) {
                $targetDomain = $refDomainSlug
                    ? Domain::query()->eligibleForSubscription()->where('slug', $refDomainSlug)->first()
                    : Domain::query()->eligibleForSubscription()->first();

                if ($targetDomain) {
                    $this->subscriptionManager->grantFreeTrial($user, $targetDomain, $trialDays);

                    $result = $this->referralService->processReferral(
                        childUser: $user,
                        referralCode: (string) $refCode,
                        domain: $targetDomain,
                        ipAddress: request()->ip(),
                        deviceFingerprint: request()->header('User-Agent')
                    );

                    if ($result['status'] === 'abuse_blocked' && ! empty($result['message'])) {
                        session()->flash('warning', $result['message']);
                    }
                }
            }

            return $user;
        });
    }
}
