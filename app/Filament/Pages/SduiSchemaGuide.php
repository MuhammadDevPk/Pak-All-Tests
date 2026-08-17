<?php

namespace App\Filament\Pages;

use App\Services\SduiSchemaValidator;
use Filament\Pages\Page;

class SduiSchemaGuide extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-code-bracket-square';

    protected static string | \UnitEnum | null $navigationGroup = 'System Documentation';

    protected static ?string $navigationLabel = 'SDUI Schema Guide';

    protected static ?string $title = 'Server-Driven UI (SDUI) Schema Guide & Validator';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.sdui-schema-guide';

    /**
     * Get JSON Templates for common exam flow structures.
     */
    public function getTemplates(): array
    {
        return [
            'phase_stepper' => [
                'name' => 'Multi-Phase Stepper Layout (PMA / Gated Exams)',
                'description' => '4-phase sequential stepper containing nested tabs, subject grids, and interactive test modules.',
                'json' => [
                    'type' => 'PhaseStepperLayout',
                    'props' => [
                        'currentPhase' => 1,
                        'phases' => [
                            [
                                'number' => 1,
                                'title' => 'Phase 1: Initial Intelligence',
                                'subtitle' => 'Verbal & Non-Verbal Intelligence Testing',
                                'content' => [
                                    'type' => 'TabbedLayout',
                                    'props' => [
                                        'defaultTab' => 'verbal',
                                        'tabs' => [
                                            [
                                                'id' => 'verbal',
                                                'label' => 'Verbal Test',
                                                'content' => [
                                                    'type' => 'McqPlayer',
                                                    'moduleId' => 1,
                                                    'props' => [
                                                        'title' => 'Verbal Intelligence Test 1',
                                                    ],
                                                ],
                                            ],
                                            [
                                                'id' => 'non_verbal',
                                                'label' => 'Non-Verbal Test',
                                                'content' => [
                                                    'type' => 'McqPlayer',
                                                    'moduleId' => 2,
                                                    'props' => [
                                                        'title' => 'Non-Verbal Intelligence Test 1',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Phase 2: Academic Test',
                                'subtitle' => 'General Knowledge & English Evaluation',
                                'content' => [
                                    'type' => 'McqPlayer',
                                    'moduleId' => 3,
                                    'props' => [
                                        'title' => 'Academic Test 1',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'subject_grid' => [
                'name' => 'Subject Grid Layout (CSS / Broad Curriculums)',
                'description' => 'Grid layout displaying compulsory and elective subject cards with instant module launcher.',
                'json' => [
                    'type' => 'SubjectGrid',
                    'props' => [
                        'columns' => 3,
                        'items' => [
                            [
                                'title' => 'English Precis & Composition',
                                'description' => 'Grammar, vocabulary, précis writing, and essay evaluation.',
                                'moduleId' => 10,
                                'badge' => 'Compulsory',
                            ],
                            [
                                'title' => 'General Science & Ability',
                                'description' => 'Basic scientific concepts, logical reasoning, and analytical skills.',
                                'moduleId' => 11,
                                'badge' => 'Compulsory',
                            ],
                            [
                                'title' => 'Islamic Studies / Comparative Religion',
                                'description' => 'Comprehensive study guide and subjective topic practice.',
                                'moduleId' => 12,
                                'badge' => 'Compulsory',
                            ],
                        ],
                    ],
                ],
            ],
            'tabbed_layout' => [
                'name' => 'Tabbed Layout (Simple Multi-Tab Dashboard)',
                'description' => 'Horizontal navigation tabs hosting resource lists, PDF viewers, and subjective uploads.',
                'json' => [
                    'type' => 'TabbedLayout',
                    'props' => [
                        'defaultTab' => 'prep',
                        'tabs' => [
                            [
                                'id' => 'prep',
                                'label' => 'Preparation Notes',
                                'content' => [
                                    'type' => 'ResourceList',
                                    'props' => [
                                        'title' => 'Official Syllabus & Reading Materials',
                                        'items' => [
                                            [
                                                'title' => 'CSS 2027 Official Syllabus Overview',
                                                'description' => 'PDF document containing rules, subjects, and marks distribution.',
                                                'fileUrl' => '/storage/syllabi/css-2027.pdf',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'id' => 'practice',
                                'label' => 'Mock Tests',
                                'content' => [
                                    'type' => 'McqPlayer',
                                    'moduleId' => 15,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
