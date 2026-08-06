<?php

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\DomainFlow;
use Illuminate\Database\Seeder;

class DomainSeeder extends Seeder
{
    public function run(): void
    {
        DomainFlow::create([
            'domain_id' => 3,
            'ui_schema' => [
                'layout' => 'two_tiered_tabs',
                'tabs' => [
                    [
                        'key' => 'preparation',
                        'label' => 'Preparation',
                        'modules' => ['verbal_intel', 'non_verbal_intel', 'academic']
                    ],
                    [
                        'key' => 'testing',
                        'label' => 'Mock Tests',
                        'modules' => ['full_mock_test']
                    ]
                ]
            ],
            'learning_schema' => [
                'time_limit_minutes' => 30,
                'passing_percentage' => 60,
                'negative_marking' => false,
            ]
        ]);

        $civilServices = Domain::create([
            'name' => 'Civil Services',
            'slug' => 'civil-services',
            'code' => 'CIVIL_SERVICES',
        ]);

        $css = Domain::create([
            'parent_id' => $civilServices->id,
            'name' => 'CSS Examination',
            'slug' => 'css-exam',
            'code' => 'CSS_2027',
        ]);

        DomainFlow::create([
            'domain_id' => $css->id,
            'ui_schema' => [
                'layout' => 'subject_grid',
                'subjects' => [
                    ['name' => 'English Precis & Composition', 'type' => 'essay_uploader'],
                    ['name' => 'General Knowledge', 'type' => 'mcq_grid'],
                    ['name' => 'Islamic Studies', 'type' => 'descriptive']
                ]
            ],
            'learning_schema' => [
                'passing_percentage' => 40,
                'essay_word_limit' => 2500,
            ]
        ]);
    }
}