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
                'total_questions' => 4,
                'description' => 'Master verbal analogy, coding-decoding, and logical series questions.',
                'instructions' => 'Complete all verbal analogy questions within the given time.',
            ],
        ]);

        $pmaNonVerbalModule = ContentModule::create([
            'domain_id' => $pma->id,
            'title' => 'Pattern Matching & Matrix Reasoning',
            'slug' => 'non-verbal-matrix-reasoning',
            'module_type' => 'preparation',
            'sort_order' => 2,
            'is_active' => true,
            'content_meta' => [
                'duration_minutes' => 15,
                'total_questions' => 2,
                'description' => '64 pattern reasoning and matrix completion questions.',
                'instructions' => 'Analyze the patterns carefully and select the missing piece.',
            ],
        ]);

        $pmaAcademicModule = ContentModule::create([
            'domain_id' => $pma->id,
            'title' => 'General Knowledge & Current Affairs',
            'slug' => 'academic-gk-current-affairs',
            'module_type' => 'preparation',
            'sort_order' => 3,
            'is_active' => true,
            'content_meta' => [
                'duration_minutes' => 30,
                'total_questions' => 2,
                'description' => 'Academic test preparation covering Pakistan Studies, GK & History.',
                'instructions' => 'Answer GK questions covering Pakistani history and geography.',
            ],
        ]);

        $pmaWatModule = ContentModule::create([
            'domain_id' => $pma->id,
            'title' => 'Word Association Test (WAT)',
            'slug' => 'word-association-test-wat',
            'module_type' => 'subjective',
            'sort_order' => 4,
            'is_active' => true,
            'content_meta' => [
                'max_word_count' => 150,
                'instructions' => 'Write down active, positive sentences for each word prompt displayed. Spend no more than 10 seconds per word.',
                'description' => 'Psychological screening test containing 3 active word prompts.',
            ],
        ]);

        $pmaSrtModule = ContentModule::create([
            'domain_id' => $pma->id,
            'title' => 'Situation Reaction Test (SRT)',
            'slug' => 'situation-reaction-test-srt',
            'module_type' => 'subjective',
            'sort_order' => 5,
            'is_active' => true,
            'content_meta' => [
                'max_word_count' => 300,
                'instructions' => 'Read each situation prompt carefully and write down your immediate, natural response to handle it.',
                'description' => 'Psychological screening test with 2 real-world crisis scenarios.',
            ],
        ]);

        $pmaInterviewModule = ContentModule::create([
            'domain_id' => $pma->id,
            'title' => 'PMA Initial Interview Prep',
            'slug' => 'pma-initial-interview-prep',
            'module_type' => 'subjective',
            'sort_order' => 6,
            'is_active' => true,
            'content_meta' => [
                'max_word_count' => 500,
                'instructions' => 'Read the standard military board questions and write your mock interview answers.',
                'description' => 'Practice prompts for PMA Selection Board oral interviews.',
            ],
        ]);

        ContentModule::create([
            'domain_id' => $pma->id,
            'title' => 'PMA Initial Verbal & Non-Verbal Formula Book',
            'slug' => 'pma-initial-verbal-non-verbal-formula-book',
            'module_type' => 'pdf_repo',
            'sort_order' => 8,
            'is_active' => true,
            'content_meta' => [
                'file_type' => 'PDF Document',
                'file_size' => '3.4 MB',
                'file_url' => '/storage/modules/pma-formula-book.pdf',
                'description' => 'Formula reference notes for verbal and non-verbal intelligence testing.',
            ],
        ]);

        ContentModule::create([
            'domain_id' => $pma->id,
            'title' => 'ISSB Psychological & Medical Guidelines',
            'slug' => 'issb-psychological-medical-guidelines',
            'module_type' => 'pdf_repo',
            'sort_order' => 9,
            'is_active' => true,
            'content_meta' => [
                'file_type' => 'PDF Document',
                'file_size' => '4.8 MB',
                'file_url' => '/storage/modules/issb-guidelines.pdf',
                'description' => 'Reference guidelines for psychological and medical screening.',
            ],
        ]);

        $pmaMockModule = ContentModule::create([
            'domain_id' => $pma->id,
            'title' => 'Full Length PMA Initial Mock',
            'slug' => 'pma-full-mock',
            'module_type' => 'mock_test',
            'sort_order' => 7,
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
                            'title' => 'Intelligence Testing',
                            'subtitle' => 'Verbal & Non-Verbal MCQs',
                            'content' => [
                                'type' => 'TabbedLayout',
                                'props' => [
                                    'defaultTab' => 'verbal',
                                    'tabs' => [
                                        [
                                            'id' => 'verbal',
                                            'label' => 'Verbal Intelligence',
                                            'badge' => 'Active',
                                            'content' => [
                                                'type' => 'SubjectGrid',
                                                'props' => [
                                                    'columns' => 2,
                                                    'items' => [
                                                        [
                                                            'moduleId' => $pmaPrepModule->id,
                                                            'title' => 'Verbal Intelligence Test 1',
                                                            'description' => 'Practice verbal reasoning & sequence questions.',
                                                            'actionLabel' => 'Start Practice',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            'id' => 'non_verbal',
                                            'label' => 'Non-Verbal Intelligence',
                                            'badge' => 'Active',
                                            'content' => [
                                                'type' => 'SubjectGrid',
                                                'props' => [
                                                    'columns' => 2,
                                                    'items' => [
                                                        [
                                                            'moduleId' => $pmaNonVerbalModule->id,
                                                            'title' => 'Pattern Matching & Matrix Reasoning',
                                                            'description' => 'Solve abstract, non-verbal matrices and logic sequences.',
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
                            'title' => 'Academic Phase',
                            'subtitle' => 'General Knowledge Exam',
                            'content' => [
                                'type' => 'SubjectGrid',
                                'props' => [
                                    'columns' => 2,
                                    'items' => [
                                        [
                                            'moduleId' => $pmaAcademicModule->id,
                                            'title' => 'General Knowledge & Current Affairs',
                                            'description' => 'GK questions on geography, history, and Pakistan studies.',
                                            'actionLabel' => 'Attempt Exam',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'number' => 3,
                            'title' => 'Psychological Screening',
                            'subtitle' => 'WAT & SRT Scenarios',
                            'content' => [
                                'type' => 'TabbedLayout',
                                'props' => [
                                    'defaultTab' => 'wat',
                                    'tabs' => [
                                        [
                                            'id' => 'wat',
                                            'label' => 'Word Association (WAT)',
                                            'badge' => 'Descriptive',
                                            'content' => [
                                                'type' => 'SubjectGrid',
                                                'props' => [
                                                    'columns' => 1,
                                                    'items' => [
                                                        [
                                                            'moduleId' => $pmaWatModule->id,
                                                            'title' => 'Word Association Test (WAT)',
                                                            'description' => 'Write prompt-driven positive sentences within constraints.',
                                                            'actionLabel' => 'Begin WAT',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            'id' => 'srt',
                                            'label' => 'Situation Reactions (SRT)',
                                            'badge' => 'Descriptive',
                                            'content' => [
                                                'type' => 'SubjectGrid',
                                                'props' => [
                                                    'columns' => 1,
                                                    'items' => [
                                                        [
                                                            'moduleId' => $pmaSrtModule->id,
                                                            'title' => 'Situation Reaction Test (SRT)',
                                                            'description' => 'Read crisis scenarios and write down your resolutions.',
                                                            'actionLabel' => 'Begin SRT',
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
                            'number' => 4,
                            'title' => 'Selection Board Prep',
                            'subtitle' => 'PMA Oral Interviews',
                            'content' => [
                                'type' => 'SubjectGrid',
                                'props' => [
                                    'columns' => 1,
                                    'items' => [
                                        [
                                            'moduleId' => $pmaInterviewModule->id,
                                            'title' => 'PMA Initial Interview Prep',
                                            'description' => 'Submit answers to standard military selection board questions.',
                                            'actionLabel' => 'Start Prep',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'number' => 5,
                            'title' => 'Study Handouts',
                            'subtitle' => 'PDF reference guides',
                            'content' => [
                                'type' => 'ResourceList',
                                'props' => [
                                    'title' => 'PMA Handouts & Notes',
                                    'moduleQuery' => [
                                        'module_type' => 'pdf_repo',
                                        'merge' => 'replace',
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

        // 1. Verbal MCQ Questions
        $qv1 = QuestionBank::create([
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

        $qv2 = QuestionBank::create([
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

        $qv3 = QuestionBank::create([
            'question_type' => 'mcq',
            'code' => 'Q-VERB-003',
            'question_text' => 'Which word does NOT belong with the others?',
            'payload' => [
                'options' => ['Leopard', 'Cougar', 'Jaguar', 'Wolf'],
                'correct_option_index' => 3,
            ],
            'difficulty_level' => 'medium',
            'explanation' => 'Leopard, Cougar, and Jaguar belong to the cat family (Felidae), whereas Wolf belongs to the dog family (Canidae).',
            'is_active' => true,
        ]);

        $qv4 = QuestionBank::create([
            'question_type' => 'mcq',
            'code' => 'Q-VERB-004',
            'question_text' => 'If CEMENT is coded as EGOEPV, then how is CLAY coded?',
            'payload' => [
                'options' => ['ENCB', 'ENCA', 'EMCA', 'DNCA'],
                'correct_option_index' => 1,
            ],
            'difficulty_level' => 'hard',
            'explanation' => 'The pattern adds +2 to each alphabet in the word. C+2=E, L+2=N, A+2=C, Y+2=A.',
            'is_active' => true,
        ]);

        $pmaPrepModule->questions()->attach($qv1->id, ['sort_order' => 1, 'marks' => 1.00]);
        $pmaPrepModule->questions()->attach($qv2->id, ['sort_order' => 2, 'marks' => 1.00]);
        $pmaPrepModule->questions()->attach($qv3->id, ['sort_order' => 3, 'marks' => 1.00]);
        $pmaPrepModule->questions()->attach($qv4->id, ['sort_order' => 4, 'marks' => 1.00]);

        // 2. Non-Verbal MCQ Questions
        $qnv1 = QuestionBank::create([
            'question_type' => 'mcq',
            'code' => 'Q-NVERB-001',
            'question_text' => 'Select the missing figure in the series: Square with 1 dot, Square with 2 dots, Square with 3 dots, Square with ?',
            'payload' => [
                'options' => ['No dots', '4 dots', '5 dots', 'Circle'],
                'correct_option_index' => 1,
            ],
            'difficulty_level' => 'easy',
            'explanation' => 'The pattern increases the number of dots inside the square by 1 at each step.',
            'is_active' => true,
        ]);

        $qnv2 = QuestionBank::create([
            'question_type' => 'mcq',
            'code' => 'Q-NVERB-002',
            'question_text' => 'Complete the analogy: Triangle is to 3 sides as Hexagon is to ?',
            'payload' => [
                'options' => ['4 sides', '5 sides', '6 sides', '8 sides'],
                'correct_option_index' => 2,
            ],
            'difficulty_level' => 'easy',
            'explanation' => 'A hexagon is a polygon with 6 sides.',
            'is_active' => true,
        ]);

        $pmaNonVerbalModule->questions()->attach($qnv1->id, ['sort_order' => 1, 'marks' => 1.00]);
        $pmaNonVerbalModule->questions()->attach($qnv2->id, ['sort_order' => 2, 'marks' => 1.00]);

        // 3. Academic GK Questions
        $qac1 = QuestionBank::create([
            'question_type' => 'mcq',
            'code' => 'Q-GK-001',
            'question_text' => 'Who was the first Prime Minister of Pakistan?',
            'payload' => [
                'options' => ['Liaquat Ali Khan', 'Quaid-e-Azam', 'Allama Iqbal', 'Ayub Khan'],
                'correct_option_index' => 0,
            ],
            'difficulty_level' => 'easy',
            'explanation' => 'Liaquat Ali Khan served as the first Prime Minister of Pakistan from 1947 until his assassination in 1951.',
            'is_active' => true,
        ]);

        $qac2 = QuestionBank::create([
            'question_type' => 'mcq',
            'code' => 'Q-GK-002',
            'question_text' => 'Which is the longest river in Pakistan?',
            'payload' => [
                'options' => ['Jhelum River', 'Chenab River', 'Ravi River', 'Indus River'],
                'correct_option_index' => 3,
            ],
            'difficulty_level' => 'easy',
            'explanation' => 'The Indus River is the longest river in Pakistan, flowing from Tibet north-west through the Himalayas.',
            'is_active' => true,
        ]);

        $pmaAcademicModule->questions()->attach($qac1->id, ['sort_order' => 1, 'marks' => 1.00]);
        $pmaAcademicModule->questions()->attach($qac2->id, ['sort_order' => 2, 'marks' => 1.00]);

        // 4. Word Association Test (WAT) Prompts
        $qwat1 = QuestionBank::create([
            'question_type' => 'short_answer',
            'code' => 'Q-WAT-001',
            'question_text' => 'Make a sentence with: DUTY',
            'payload' => [],
            'difficulty_level' => 'medium',
            'explanation' => 'WAT sentences should reflect positive values, initiative, and responsibility.',
            'is_active' => true,
        ]);

        $qwat2 = QuestionBank::create([
            'question_type' => 'short_answer',
            'code' => 'Q-WAT-002',
            'question_text' => 'Make a sentence with: COURAGE',
            'payload' => [],
            'difficulty_level' => 'medium',
            'explanation' => 'Formulate a sentence highlighting resilience and mental strength.',
            'is_active' => true,
        ]);

        $qwat3 = QuestionBank::create([
            'question_type' => 'short_answer',
            'code' => 'Q-WAT-003',
            'question_text' => 'Make a sentence with: FEAR',
            'payload' => [],
            'difficulty_level' => 'medium',
            'explanation' => 'Show positive overcoming or logical neutral reaction to fear.',
            'is_active' => true,
        ]);

        $pmaWatModule->questions()->attach($qwat1->id, ['sort_order' => 1, 'marks' => 1.00]);
        $pmaWatModule->questions()->attach($qwat2->id, ['sort_order' => 2, 'marks' => 1.00]);
        $pmaWatModule->questions()->attach($qwat3->id, ['sort_order' => 3, 'marks' => 1.00]);

        // 5. Situation Reaction Test (SRT) Prompts
        $qsrt1 = QuestionBank::create([
            'question_type' => 'descriptive',
            'code' => 'Q-SRT-001',
            'question_text' => 'Situation: You are returning home late at night and see three thugs harassing a woman on a quiet street. What will you do?',
            'payload' => [],
            'difficulty_level' => 'medium',
            'explanation' => 'Action should be practical, quick, and courageous. E.g., shout to gather attention, call the emergency hotline immediately, and step in with whatever tool is available to deter them while keeping safety in mind.',
            'is_active' => true,
        ]);

        $qsrt2 = QuestionBank::create([
            'question_type' => 'descriptive',
            'code' => 'Q-SRT-002',
            'question_text' => 'Situation: You are in charge of a picnic team and one of your teammates gets bitten by a venomous snake in a remote forest area. What will you do?',
            'payload' => [],
            'difficulty_level' => 'medium',
            'explanation' => 'Logical immediate action: Tie a clean bandage slightly above the bite area to restrict venom flow, keep the limb static and lower than the heart, call emergency dispatch, and transport the victim to the nearest hospital immediately.',
            'is_active' => true,
        ]);

        $pmaSrtModule->questions()->attach($qsrt1->id, ['sort_order' => 1, 'marks' => 1.00]);
        $pmaSrtModule->questions()->attach($qsrt2->id, ['sort_order' => 2, 'marks' => 1.00]);

        // 6. Interview Preparation Prompts
        $qint1 = QuestionBank::create([
            'question_type' => 'descriptive',
            'code' => 'Q-INT-001',
            'question_text' => 'Why do you want to join the Pakistan Army as a commissioned officer?',
            'payload' => [],
            'difficulty_level' => 'medium',
            'explanation' => 'Answers should reflect authentic patriotism, desire for structured leadership, and professional growth in service of the nation.',
            'is_active' => true,
        ]);

        $qint2 = QuestionBank::create([
            'question_type' => 'descriptive',
            'code' => 'Q-INT-002',
            'question_text' => 'What is your biggest personal weakness and how are you working to overcome it?',
            'payload' => [],
            'difficulty_level' => 'medium',
            'explanation' => 'Be honest but highlight positive active self-improvement (e.g. public speaking hesitation but practicing in debate circles).',
            'is_active' => true,
        ]);

        $pmaInterviewModule->questions()->attach($qint1->id, ['sort_order' => 1, 'marks' => 1.00]);
        $pmaInterviewModule->questions()->attach($qint2->id, ['sort_order' => 2, 'marks' => 1.00]);

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
