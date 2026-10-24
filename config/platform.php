<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Platform Brand & Identity
    |--------------------------------------------------------------------------
    */
    'name' => env('PLATFORM_NAME', env('APP_NAME', 'Pak-All-Tests')),
    'portal_name' => env('PLATFORM_PORTAL_NAME', 'Pak-All-Tests Portal'),
    'tagline' => env('PLATFORM_TAGLINE', 'Build Knowledge That Improves Itself'),
    'subtitle' => env('PLATFORM_SUBTITLE', 'Exam Prep Kernel'),
    'description' => env('PLATFORM_DESCRIPTION', 'Recursive self-improvement preparation portal for competitive civil and military services.'),

    /*
    |--------------------------------------------------------------------------
    | Support & Contact Channels
    |--------------------------------------------------------------------------
    */
    'support_email' => env('SUPPORT_EMAIL', 'support@paktests.pk'),
    'support_phone' => env('SUPPORT_PHONE', '+923000000000'),
    'whatsapp_support_number' => env('WHATSAPP_SUPPORT_NUMBER', '+923000000000'),
    'whatsapp_support_url' => env('WHATSAPP_SUPPORT_URL', 'https://wa.me/923000000000'),

    /*
    |--------------------------------------------------------------------------
    | Candidate Access & Trial Durations
    |--------------------------------------------------------------------------
    */
    'trials' => [
        // Number of free trial days granted upon registered account creation
        'free_trial_days' => (int) env('FREE_TRIAL_DAYS', 7),

        // Duration of an ephemeral guest pass before conversion is required (in hours)
        'guest_pass_hours' => (int) env('GUEST_PASS_HOURS', 24),

        // Number of trial days provisioned when a guest visits an interactive domain flow
        'guest_domain_pass_days' => (int) env('GUEST_DOMAIN_PASS_DAYS', 1),

        // Domain suffix used for guest dummy emails (e.g. guest_xyz@guest.pakalltests.local)
        'guest_email_domain' => env('GUEST_EMAIL_DOMAIN', 'guest.pakalltests.local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Referral Growth System
    |--------------------------------------------------------------------------
    */
    'referrals' => [
        // Days awarded to parent for each referred friend
        'days_per_referral' => (int) env('REFERRAL_DAYS_PER_FRIEND', 7),

        // Number of referred friends required to hit milestone bonus
        'milestone_target' => (int) env('REFERRAL_MILESTONE_TARGET', 5),

        // Bonus access days granted upon hitting the milestone target
        'milestone_days' => (int) env('REFERRAL_MILESTONE_DAYS', 30),

        // Default WhatsApp viral share message template
        'share_message_template' => env(
            'REFERRAL_SHARE_TEMPLATE',
            'Join me on :platform! Practice authentic past-paper MCQs, take timed exams, and get instant Socratic AI mentorship: :link'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways & Receiving Accounts
    |--------------------------------------------------------------------------
    */
    'payment_accounts' => [
        'easypaisa' => [
            'name' => 'EasyPaisa Mobile Account',
            'accountTitle' => env('EASYPAISA_TITLE', 'Pak-All-Tests Academy'),
            'account_title' => env('EASYPAISA_TITLE', 'Pak-All-Tests Academy'),
            'accountNumber' => env('EASYPAISA_NUMBER', '0345-8899112'),
            'account_number' => env('EASYPAISA_NUMBER', '0345-8899112'),
            'instructions' => 'Send test enrollment fee to the EasyPaisa account above. Save the Transaction ID (TRX ID) from the confirmation SMS.',
        ],
        'jazzcash' => [
            'name' => 'JazzCash Wallet',
            'accountTitle' => env('JAZZCASH_TITLE', 'Pak-All-Tests Official'),
            'account_title' => env('JAZZCASH_TITLE', 'Pak-All-Tests Official'),
            'accountNumber' => env('JAZZCASH_NUMBER', '0300-5544332'),
            'account_number' => env('JAZZCASH_NUMBER', '0300-5544332'),
            'instructions' => 'Transfer to the JazzCash mobile number above. Take a screenshot of the digital receipt upon successful payment.',
        ],
        'bank_transfer' => [
            'name' => 'Meezan Bank Limited',
            'accountTitle' => env('BANK_TRANSFER_TITLE', 'Pak-All-Tests National Portal'),
            'account_title' => env('BANK_TRANSFER_TITLE', 'Pak-All-Tests National Portal'),
            'accountNumber' => env('BANK_TRANSFER_NUMBER', '0102-0105489201'),
            'account_number' => env('BANK_TRANSFER_NUMBER', '0102-0105489201'),
            'iban' => env('BANK_TRANSFER_IBAN', 'PK45MEZN0001020105489201'),
            'instructions' => 'Make an IBFT transfer to Meezan Bank. Attach the digital receipt screenshot or PDF bank statement.',
        ],
    ],
];
