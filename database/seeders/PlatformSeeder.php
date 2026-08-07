<?php

namespace Database\Seeders;

use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\DomainFlow;
use App\Models\QuestionBank;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure test users exist
        $admin = User::firstOrCreate(
            ['email' => 'admin@paktests.pk'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
            ]
        );

        $student = User::firstOrCreate(
            ['email' => 'student@paktests.pk'],
            [
                'name' => 'Aspirant Student',
                'password' => bcrypt('password'),
            ]
        );

        // 2. Clear old module data cleanly if re-running
        DomainFlow::query()->delete();
        ContentModule::query()->delete();
        QuestionBank::query()->delete();
        UserSubscription::query()->delete();
        Domain::query()->delete();

        // -------------------------------------------------------------
        // DOMAIN 1: Armed Forces -> Pakistan Army -> PMA Initial
        // -------------------------------------------------------------
        $armedForces = Domain::create([
            'name' => 'Armed Forces',
            'slug' => 'armed-forces',
            'code' => 'ARMED_FORCES',
            'is_active' => true,
        ]);

        $army = Domain::create([
            'parent_id' => $armedForces->id,
            'name' => 'Pakistan Army',
            'slug' => 'pakistan-army',
            'code' => 'PAK_ARMY',
            'is_active' => true,
        ]);

        $pma = Domain::create([
            'parent_id' => $army->id,
            'name' => 'PMA Long Course Initial',
            'slug' => 'pma-initial-course',
            'code' => 'PMA_INITIAL',
            'is_active' => true,
        ]);

        // SDUI Schema for PMA Initial
        DomainFlow::create([
            'domain_id' => $pma->id,
            'version' => 1,
            'is_active' => true,
            'ui_schema' => [
                'layout' => 'two_tiered_tabs',
                'tabs' => [
                    [
                        'key' => 'preparation',
                        'label' => 'Preparation Phase',
                        'modules' => ['verbal_intelligence', 'non_verbal_intelligence', 'academic_general'],
                    ],
                    [
                        'key' => 'mock_tests',
                        'label' => 'Mock Examination',
                        'modules' => ['pma_full_mock'],
                    ],
                ],
            ],
            'learning_schema' => [
                'time_limit_minutes' => 45,
                'passing_percentage' => 60,
                'negative_marking' => false,
                'gating' => [
                    'verbal_must_pass' => true,
                ],
            ],
        ]);

        // Content Modules for PMA Initial
        $pmaPrepModule = ContentModule::create([
            'domain_id' => $pma->id,
            'title' => 'Verbal Intelligence Test 1',
            'slug' => 'verbal-intelligence-test-1',
            'module_type' => 'preparation',
            'sort_order' => 1,
            'is_active' => true,
            'content_meta' => [
                'duration_minutes' => 20,
                'total_questions' => 10,
                'instructions' => 'Complete all verbal analogy questions within the given time.',
            ],
        ]);

        $pmaMockModule = ContentModule::create([
            'domain_id' => $pma->id,
            'title' => 'Full Length PMA Initial Mock',
            'slug' => 'pma-full-mock',
            'module_type' => 'mock_test',
            'sort_order' => 2,
            'is_active' => true,
            'content_meta' => [
                'duration_minutes' => 45,
                'passing_score' => 60,
            ],
        ]);

        // -------------------------------------------------------------
        // DOMAIN 2: Civil Services -> CSS Examination
        // -------------------------------------------------------------
        $civilServices = Domain::create([
            'name' => 'Civil Services',
            'slug' => 'civil-services',
            'code' => 'CIVIL_SERVICES',
            'is_active' => true,
        ]);

        $css = Domain::create([
            'parent_id' => $civilServices->id,
            'name' => 'CSS Competitive Exam 2027',
            'slug' => 'css-exam-2027',
            'code' => 'CSS_2027',
            'is_active' => true,
        ]);

        DomainFlow::create([
            'domain_id' => $css->id,
            'version' => 1,
            'is_active' => true,
            'ui_schema' => [
                'layout' => 'subject_grid',
                'subjects' => [
                    ['name' => 'English Precis & Composition', 'type' => 'essay_uploader'],
                    ['name' => 'General Knowledge & Current Affairs', 'type' => 'mcq_grid'],
                    ['name' => 'Islamic Studies', 'type' => 'descriptive'],
                ],
            ],
            'learning_schema' => [
                'passing_percentage' => 40,
                'essay_word_limit' => 2500,
                'allow_rebooking' => true,
            ],
        ]);

        $cssEssayModule = ContentModule::create([
            'domain_id' => $css->id,
            'title' => 'English Essay Evaluation Module',
            'slug' => 'english-essay-evaluation',
            'module_type' => 'preparation',
            'sort_order' => 1,
            'is_active' => true,
            'content_meta' => [
                'max_word_count' => 2500,
                'rubric' => 'Structure, Thesis Statement, Argumentation, Grammar',
            ],
        ]);

        // -------------------------------------------------------------
        // QUESTION BANK SEEDING
        // -------------------------------------------------------------
        $q1 = QuestionBank::create([
            'question_type' => 'mcq',
            'code' => 'Q-VERB-001',
            'question_text' => 'What comes next in the sequence: 2, 4, 8, 16, ?',
            'payload' => [
                'options' => ['20', '24', '32', '64'],
                'correct_option_index' => 2,
            ],
            'difficulty_level' => 'easy',
            'explanation' => 'Each term is multiplied by 2 to get the subsequent term. 16 * 2 = 32.',
            'is_active' => true,
        ]);

        $q2 = QuestionBank::create([
            'question_type' => 'mcq',
            'code' => 'Q-VERB-002',
            'question_text' => 'Light is to Eye as Sound is to ?',
            'payload' => [
                'options' => ['Ear', 'Nose', 'Tongue', 'Hand'],
                'correct_option_index' => 0,
            ],
            'difficulty_level' => 'easy',
            'explanation' => 'Eye perceives light; Ear perceives sound.',
            'is_active' => true,
        ]);

        $q3 = QuestionBank::create([
            'question_type' => 'essay',
            'code' => 'Q-CSS-ESSAY-001',
            'question_text' => 'Analyze the economic challenges facing Pakistan and propose structural reforms.',
            'payload' => [
                'word_limit' => 2500,
                'key_themes' => ['Fiscal Deficit', 'IMF Bailouts', 'Tax Base Expansion', 'Export Competitiveness'],
            ],
            'difficulty_level' => 'hard',
            'explanation' => 'Candidate must construct a structured 2500-word essay with a clear outline.',
            'is_active' => true,
        ]);

        // Attach Questions to Content Modules via Pivot
        $pmaPrepModule->questions()->attach($q1->id, ['sort_order' => 1, 'marks' => 1.00]);
        $pmaPrepModule->questions()->attach($q2->id, ['sort_order' => 2, 'marks' => 1.00]);
        $cssEssayModule->questions()->attach($q3->id, ['sort_order' => 1, 'marks' => 100.00]);

        // -------------------------------------------------------------
        // USER SUBSCRIPTIONS SEEDING
        // -------------------------------------------------------------
        UserSubscription::create([
            'user_id' => $student->id,
            'domain_id' => $pma->id,
            'status' => 'active',
            'payment_method' => 'easypaisa',
            'transaction_id' => 'TRX-9988776655',
            'receipt_url' => 'receipts/sample_pma_receipt.png',
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        UserSubscription::create([
            'user_id' => $student->id,
            'domain_id' => $css->id,
            'status' => 'pending',
            'payment_method' => 'jazzcash',
            'transaction_id' => 'TRX-1122334455',
            'receipt_url' => 'receipts/sample_css_receipt.png',
            'starts_at' => null,
            'expires_at' => null,
        ]);
    }
}
