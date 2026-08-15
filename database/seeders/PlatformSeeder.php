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
                'description' => 'Master verbal analogy, coding-decoding, and logical series questions.',
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
                'description' => 'Complete timed initial test simulation covering Verbal, Non-Verbal & Academic.',
            ],
        ]);

        // Dynamic SDUI Schema for PMA Initial (Multi-Phase Stepper + Nested Tabs)
        DomainFlow::create([
            'domain_id' => $pma->id,
            'version' => 1,
            'is_active' => true,
            'ui_schema' => [
                'type' => 'PhaseStepperLayout',
                'props' => [
                    'currentPhase' => 1,
                    'phases' => [
                        [
                            'number' => 1,
                            'title' => 'Initial Intelligence Test',
                            'subtitle' => 'Verbal & Non-Verbal MCQs',
                            'content' => [
                                'type' => 'TabbedLayout',
                                'props' => [
                                    'defaultTab' => 'verbal',
                                    'tabs' => [
                                        [
                                            'id' => 'verbal',
                                            'label' => 'Verbal Intelligence',
                                            'badge' => '1 Active Test',
                                            'content' => [
                                                'type' => 'SubjectGrid',
                                                'props' => [
                                                    'columns' => 2,
                                                    'items' => [
                                                        [
                                                            'moduleId' => $pmaPrepModule->id,
                                                            'title' => 'Verbal Intelligence Test 1',
                                                            'description' => '84 Analogy & Reasoning MCQs',
                                                            'actionLabel' => 'Start Practice',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            'id' => 'non_verbal',
                                            'label' => 'Non-Verbal Intelligence',
                                            'badge' => '1 Active Test',
                                            'content' => [
                                                'type' => 'SubjectGrid',
                                                'props' => [
                                                    'columns' => 2,
                                                    'items' => [
                                                        [
                                                            'title' => 'Pattern Matching & Matrix Reasoning',
                                                            'description' => '64 Pattern Identification Questions',
                                                            'actionLabel' => 'Start Practice',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'number' => 2,
                            'title' => 'Academic Test Phase',
                            'subtitle' => 'Math, English & General Knowledge',
                            'content' => [
                                'type' => 'SubjectGrid',
                                'props' => [
                                    'columns' => 2,
                                    'items' => [
                                        [
                                            'title' => 'General Knowledge & Current Affairs',
                                            'description' => '50 Questions on Pakistan Studies & World History',
                                            'actionLabel' => 'Attempt Exam',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'number' => 3,
                            'title' => 'Official Handouts & Notes',
                            'subtitle' => 'PDF Reference Guides',
                            'content' => [
                                'type' => 'ResourceList',
                                'props' => [
                                    'title' => 'Preparation Handouts',
                                    'items' => [
                                        [
                                            'title' => 'PMA Initial Verbal & Non-Verbal Formula Book',
                                            'fileType' => 'PDF Document',
                                            'fileSize' => '3.4 MB',
                                            'actionLabel' => 'Download Notes',
                                        ],
                                        [
                                            'title' => 'ISSB Psychological & Medical Guidelines',
                                            'fileType' => 'PDF Document',
                                            'fileSize' => '4.8 MB',
                                            'actionLabel' => 'Download Notes',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'learning_schema' => [
                'time_limit_minutes' => 45,
                'passing_percentage' => 60,
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

        $cssEssayModule = ContentModule::create([
            'domain_id' => $css->id,
            'title' => 'English Essay Evaluation Module',
            'slug' => 'english-essay-evaluation',
            'module_type' => 'preparation',
            'sort_order' => 1,
            'is_active' => true,
            'content_meta' => [
                'max_word_count' => 2500,
                'description' => 'Submit written essays for evaluation by expert CSS mentors.',
            ],
        ]);

        DomainFlow::create([
            'domain_id' => $css->id,
            'version' => 1,
            'is_active' => true,
            'ui_schema' => [
                'type' => 'TabbedLayout',
                'props' => [
                    'defaultTab' => 'compulsory',
                    'tabs' => [
                        [
                            'id' => 'compulsory',
                            'label' => 'Compulsory Subjects',
                            'badge' => '2 Modules',
                            'content' => [
                                'type' => 'VerticalStack',
                                'props' => [
                                    'children' => [
                                        [
                                            'type' => 'ProgressCard',
                                            'props' => [
                                                'accuracy' => 84,
                                                'completedCount' => 3,
                                                'totalCount' => 5,
                                            ],
                                        ],
                                        [
                                            'type' => 'SubjectGrid',
                                            'props' => [
                                                'columns' => 2,
                                                'items' => [
                                                    [
                                                        'moduleId' => $cssEssayModule->id,
                                                        'title' => 'English Essay & Outline',
                                                        'description' => '2500 Word Essay Evaluation with Detailed Rubrics',
                                                        'actionLabel' => 'Submit Essay',
                                                    ],
                                                    [
                                                        'title' => 'General Science & Ability (MPT MCQs)',
                                                        'description' => 'Screening test practice question bank',
                                                        'actionLabel' => 'Start Practice',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'id' => 'resources',
                            'label' => 'Past Papers & Notes',
                            'badge' => '2 Files',
                            'content' => [
                                'type' => 'ResourceList',
                                'props' => [
                                    'title' => 'CSS 2015-2026 Past Papers Compilation',
                                    'items' => [
                                        [
                                            'title' => 'CSS English Precis Past Papers (10 Years)',
                                            'fileType' => 'PDF Document',
                                            'fileSize' => '8.2 MB',
                                            'actionLabel' => 'Open PDF',
                                        ],
                                        [
                                            'title' => 'Current Affairs Monthly Digest - August 2026',
                                            'fileType' => 'PDF Document',
                                            'fileSize' => '12.5 MB',
                                            'actionLabel' => 'Open PDF',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'learning_schema' => [
                'passing_percentage' => 40,
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

        $pmaPrepModule->questions()->attach($q1->id, ['sort_order' => 1, 'marks' => 1.00]);
        $pmaPrepModule->questions()->attach($q2->id, ['sort_order' => 2, 'marks' => 1.00]);

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
            'status' => 'active',
            'payment_method' => 'jazzcash',
            'transaction_id' => 'TRX-1122334455',
            'receipt_url' => 'receipts/sample_css_receipt.png',
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
    }
}
